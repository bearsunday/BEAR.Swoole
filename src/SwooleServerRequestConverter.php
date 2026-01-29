<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Http\Request;

use function array_change_key_case;
use function is_array;
use function is_scalar;
use function is_string;
use function str_replace;

use const CASE_UPPER;

final readonly class SwooleServerRequestConverter
{
    public function __construct(
        private ServerRequestFactoryInterface $serverRequestFactory,
        private UriFactoryInterface $uriFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory,
        private StreamFactoryInterface $streamFactory
    ) {
    }

    public function createFromSwoole(Request $swooleRequest): ServerRequestInterface
    {
        $server = self::toGlobals($swooleRequest);
        $method = isset($server['REQUEST_METHOD']) && is_string($server['REQUEST_METHOD'])
            ? $server['REQUEST_METHOD']
            : 'GET';
        $headers = $swooleRequest->header ?? [];
        $uri = $this->parseUri($server);

        $serverRequest = $this->serverRequestFactory->createServerRequest(
            $method,
            $uri,
            $server
        );

        $serverRequest = $this->addHeaders($headers, $serverRequest);

        $content = is_string($swooleRequest->rawContent()) ? $swooleRequest->rawContent() : '';
        $stream = $this->streamFactory->createStream($content);
        $stream->rewind();

        return $serverRequest
            ->withProtocolVersion($this->parseProtocol($server))
            ->withCookieParams($swooleRequest->cookie ?? [])
            ->withQueryParams($swooleRequest->get ?? [])
            ->withParsedBody($swooleRequest->post ?? [])
            ->withBody($stream)
            ->withUploadedFiles($this->parseUploadedFiles($swooleRequest->files ?? []));
    }

    /**
     * @param array<string, string> $headers
     */
    private function addHeaders(array $headers, ServerRequestInterface $serverRequest): ServerRequestInterface
    {
        foreach ($headers as $name => $value) {
            if ($serverRequest->hasHeader($name)) {
                continue;
            }

            $serverRequest = $serverRequest->withAddedHeader($name, $value);
        }

        return $serverRequest;
    }

    /**
     * Convert Swoole request to a standard $_SERVER compatible array.
     *
     * @return array<string, mixed>
     */
    public static function toGlobals(Request $request): array
    {
        $server = array_change_key_case($request->server ?? [], CASE_UPPER);
        foreach ($request->header ?? [] as $name => $value) {
            $key = 'HTTP_' . str_replace('-', '_', strtoupper((string) $name));
            if ($name === 'content-type') {
                $key = 'CONTENT_TYPE';
            }
            if ($name === 'content-length') {
                $key = 'CONTENT_LENGTH';
            }
            $server[$key] = $value;
        }

        return $server;
    }

    /**
     * @param array<string, mixed> $server
     */
    private function parseProtocol(array $server): string
    {
        $defaultProtocol = '1.1';
        $protocol = $server['SERVER_PROTOCOL'] ?? null;

        return is_scalar($protocol) ? str_replace('HTTP/', '', (string) $protocol) : $defaultProtocol;
    }

    /**
     * @param array<string, mixed> $server
     */
    private function parseUri(array $server): UriInterface
    {
        $uri = $this->uriFactory->createUri();
        $uri = $uri->withScheme($this->detectScheme($server));
        $uri = $this->applyHost($uri, $server);
        $uri = $this->applyPort($uri, $server);
        $uri = $this->applyPath($uri, $server);

        return $this->applyQuery($uri, $server);
    }

    /** @param array<string, mixed> $server */
    private function detectScheme(array $server): string
    {
        $xForwardedProto = $server['HTTP_X_FORWARDED_PROTO'] ?? null;
        if (is_scalar($xForwardedProto)) {
            return (string) $xForwardedProto;
        }

        $https = $server['HTTPS'] ?? null;
        if (is_scalar($https) && $https !== 'off') {
            return 'https';
        }

        return 'http';
    }

    /** @param array<string, mixed> $server */
    private function applyHost(UriInterface $uri, array $server): UriInterface
    {
        $httpHost = $server['HTTP_HOST'] ?? null;
        if (is_scalar($httpHost)) {
            return $uri->withHost((string) $httpHost);
        }

        $serverName = $server['SERVER_NAME'] ?? null;
        if (is_scalar($serverName)) {
            return $uri->withHost((string) $serverName);
        }

        return $uri;
    }

    /** @param array<string, mixed> $server */
    private function applyPort(UriInterface $uri, array $server): UriInterface
    {
        $serverPort = $server['SERVER_PORT'] ?? null;

        return is_scalar($serverPort) ? $uri->withPort((int) $serverPort) : $uri;
    }

    /** @param array<string, mixed> $server */
    private function applyPath(UriInterface $uri, array $server): UriInterface
    {
        $requestUri = $server['REQUEST_URI'] ?? null;

        return is_scalar($requestUri) ? $uri->withPath((string) $requestUri) : $uri;
    }

    /** @param array<string, mixed> $server */
    private function applyQuery(UriInterface $uri, array $server): UriInterface
    {
        $queryString = $server['QUERY_STRING'] ?? null;

        return is_scalar($queryString) ? $uri->withQuery((string) $queryString) : $uri;
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     *
     * @return array<string, mixed>
     */
    private function parseUploadedFiles(array $uploadedFiles): array
    {
        $parsed = [];
        foreach ($uploadedFiles as $key => $file) {
            $uploadedFile = $this->createUploadedFile($file);
            if ($uploadedFile !== null) {
                $parsed[$key] = $uploadedFile;
            }
        }

        return $parsed;
    }

    /** @param mixed $file */
    private function createUploadedFile($file): ?\Psr\Http\Message\UploadedFileInterface
    {
        if (! $this->isValidUploadedFile($file)) {
            return null;
        }

        /** @var array{tmp_name: string, size?: mixed, error?: mixed, name?: mixed, type?: mixed} $file */
        return $this->uploadedFileFactory->createUploadedFile(
            $this->streamFactory->createStreamFromFile($file['tmp_name']),
            $this->extractInt($file, 'size'),
            $this->extractInt($file, 'error') ?? 0,
            $this->extractString($file, 'name'),
            $this->extractString($file, 'type')
        );
    }

    /** @param mixed $file */
    private function isValidUploadedFile($file): bool
    {
        return is_array($file) && isset($file['tmp_name']) && is_string($file['tmp_name']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_scalar($value) ? (int) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractString(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }
}
