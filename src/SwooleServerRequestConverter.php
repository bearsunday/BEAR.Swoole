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
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly UploadedFileFactoryInterface $uploadedFileFactory,
        private readonly StreamFactoryInterface $streamFactory
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

        $xForwardedProto = $server['HTTP_X_FORWARDED_PROTO'] ?? null;
        $https = $server['HTTPS'] ?? null;
        if (is_scalar($xForwardedProto)) {
            $uri = $uri->withScheme((string) $xForwardedProto);
        } elseif (is_scalar($https) && $https !== 'off') {
            $uri = $uri->withScheme('https');
        } else {
            $uri = $uri->withScheme('http');
        }

        $httpHost = $server['HTTP_HOST'] ?? null;
        $serverName = $server['SERVER_NAME'] ?? null;
        if (is_scalar($httpHost)) {
            $uri = $uri->withHost((string) $httpHost);
        } elseif (is_scalar($serverName)) {
            $uri = $uri->withHost((string) $serverName);
        }

        $serverPort = $server['SERVER_PORT'] ?? null;
        if (is_scalar($serverPort)) {
            $uri = $uri->withPort((int) $serverPort);
        }

        $requestUri = $server['REQUEST_URI'] ?? null;
        if (is_scalar($requestUri)) {
            $uri = $uri->withPath((string) $requestUri);
        }

        $queryString = $server['QUERY_STRING'] ?? null;
        if (is_scalar($queryString)) {
            $uri = $uri->withQuery((string) $queryString);
        }

        return $uri;
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
            if (! is_array($file) || ! isset($file['tmp_name']) || ! is_string($file['tmp_name'])) {
                continue;
            }

            $parsed[$key] = $this->uploadedFileFactory->createUploadedFile(
                $this->streamFactory->createStreamFromFile($file['tmp_name']),
                isset($file['size']) ? (int) $file['size'] : null,
                isset($file['error']) ? (int) $file['error'] : 0,
                isset($file['name']) && is_string($file['name']) ? $file['name'] : null,
                isset($file['type']) && is_string($file['type']) ? $file['type'] : null
            );
        }

        return $parsed;
    }
}
