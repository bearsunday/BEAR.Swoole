<?php

declare(strict_types=1);

use BEAR\AppMeta\Meta;
use BEAR\Package\Module;
use BEAR\Package\Module\ResourceObjectModule;
use BEAR\Resource\Method;
use BEAR\Swoole\App;
use BEAR\Swoole\SwooleModule;
use BEAR\Swoole\SwooleRequestProvider;
use Ray\PsrCacheModule\Annotation\CacheDir;
use Ray\Di\Injector;
use Swoole\Coroutine;
use Swoole\Atomic;
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

    // Done in the master, once, for its side effects on disk:
    //  - building the container weaves the aspects, and Ray\Aop\Compiler writes those proxies
    //    with file_exists-then-write and no lock
    //  - resolving #[CacheDir] creates the cache directory, whose provider throws rather than
    //    re-checking when it loses a concurrent mkdir (bearsunday/BEAR.Package#505)
    // Both would otherwise be raced by workers starting at once. Neither leaves an instance
    // behind that a worker could inherit: the container instantiates nothing, and the cache
    // directory is a string.
    (new Injector($module, $classDir))->getInstance('', CacheDir::class);

    $app = null;

    $http = new Server($ip, $port);
    // Set explicitly rather than read back: Server::$setting is empty in the master before
    // start(), so the readiness count below would have nothing to compare against. 1 is
    // Swoole's own default for worker_num.
    $settings += ['worker_num' => 1];
    $http->set($settings);
    $workerNum = (int) $settings['worker_num'];
    // Shared across the fork, so the worker that finishes last can say so.
    $booted = new Atomic(0);

    // Instances belong to the worker that serves with them: a graph built before start() is
    // inherited by every worker, which then share the handles its singletons hold.
    //
    // Nothing here may yield. Coroutine hooks are on, so a suspension inside this callback
    // hands the worker to its event loop and requests arrive before $app is set.
    $http->on('workerStart', static function (Server $server, int $workerId) use (&$app, $module, $classDir, $booted, $workerNum, $ip, $port): void {
        $app = (new Injector($module, $classDir))->getInstance(App::class);

        // The banner is the readiness signal, so it waits for the last worker: until then some
        // worker still answers 503. on('start') would fire in the master, before any of this.
        if ($server->taskworker || $booted->add(1) !== $workerNum) {
            return;
        }

        echo "Swoole http server is started at http://{$ip}:{$port}" . PHP_EOL;
    });

    $http->on('request', static function (Request $request, Response $response) use (&$app): void {
        if (! $app instanceof App) {
            // This worker is still in workerStart; it has nothing to serve with yet.
            $response->status(503);
            $response->header('Retry-After', '1');
            $response->end();

            return;
        }

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
