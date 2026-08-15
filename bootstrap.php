<?php

declare(strict_types=1);

use BEAR\AppMeta\Meta;
use BEAR\Package\Module;
use BEAR\Package\Module\ResourceObjectModule;
use BEAR\Resource\Method;
use BEAR\Swoole\App;
use BEAR\Swoole\SwooleModule;
use BEAR\Swoole\SwooleRequestProvider;
use Ray\Di\Injector;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

// Enable coroutine hooks for all I/O operations (PDO, MySQL, Redis, curl, file, etc.)
Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);

/**
 * @return int Exit code
 */
return static function (string $context, string $name, string $ip, int $port, array $settings = []): int {
    $meta = new Meta($name, $context);
    $appModule = (new Module())($meta, $context);
    $appModule->override(new SwooleModule());
    $module = new SwooleModule($appModule);
    // Bind every ResourceObject up front. One left untargeted is bound on first request
    // instead, and Ray.Aop then writes its proxy while other coroutines are running.
    $module->install(new ResourceObjectModule($meta->getResourceListGenerator()));
    $classDir = $meta->tmpDir;

    // Weaving here, in the master, keeps Ray\Aop\Compiler's unlocked proxy writes out of the
    // workers. Only the container is built; nothing is instantiated yet.
    new Injector($module, $classDir);
    $bootLock = $classDir . '/worker-boot.lock';

    $app = null;

    $http = new Server($ip, $port);
    $http->set($settings);

    $http->on('start', static function (Server $server) use ($ip, $port): void {
        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;
    });

    // Instances belong to the worker that serves with them: a graph built before start() is
    // inherited by every worker, which then share the handles its singletons hold.
    //
    // Serialised, because workers start at once and the graph still does first-time filesystem
    // work with check-then-act - BEAR\Package\Provide\Cache\CacheDirProvider throws when it
    // loses the mkdir. The first worker through does that work; the rest find it done.
    $http->on('workerStart', static function (Server $server, int $workerId) use (&$app, $module, $classDir, $bootLock): void {
        $lock = fopen($bootLock, 'c');
        if ($lock !== false) {
            flock($lock, LOCK_EX);
        }

        try {
            $app = (new Injector($module, $classDir))->getInstance(App::class);
        } finally {
            if ($lock !== false) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    });

    $http->on('request', static function (Request $request, Response $response) use (&$app): void {
        assert($app instanceof App); // workerStart runs before this worker sees a request
        // Seed the context for potential PSR-7 use. Conversion is lazy.
        $server = SwooleRequestProvider::seed($request);

        try {
            // Check ETag from coroutine context directly.
            if ($app->httpCache->isNotModified()) {
                $app->httpCache->transfer($response);

                return;
            }

            $match = $app->router->match(
                [
                    '_GET' => $request->get ?? [],
                    '_POST' => $request->post ?? [],
                ],
                $server,
            );

            $ro = $app->resource->newRequest(Method::from($match->method), $match->path, $match->query)();

            $app->responder->setResponse($response);
            $ro->transfer($app->responder, []);
        } catch (Throwable $e) {
            $app->error->transfer($e, $request, $response);
        }
    });

    $http->start();

    return 0;
};
