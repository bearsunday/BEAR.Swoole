<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\Swoole\Exception\RequestNotSeededException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Coroutine;

final class SwooleRequestProxy implements ServerRequestInterface
{
    public function __construct(
        private readonly SwooleServerRequestConverter $converter,
    ) {
    }

    private function getRequest(): ServerRequestInterface
    {
        $currentCid = Coroutine::getCid();
        $cid = $currentCid;

        while ($cid !== -1 && $cid !== false) {
            /** @var ArrayObject<string, mixed>|null $context */
            $context = Coroutine::getContext($cid);
            if ($context === null) {
                break;
            }

            // 1. Try cached PSR-7 request
            if (isset($context[ServerRequestInterface::class])) {
                /** @var ServerRequestInterface $psr7Request */
                $psr7Request = $context[ServerRequestInterface::class];
                if ($cid !== $currentCid) {
                    /** @var ArrayObject<string, mixed>|null $currentContext */
                    $currentContext = Coroutine::getContext($currentCid);
                    if ($currentContext !== null) {
                        $currentContext[ServerRequestInterface::class] = $psr7Request; // Cache in current context
                    }
                }

                return $psr7Request;
            }

            // 2. Try raw Swoole request seeded by bootstrap
            $swooleRequest = $context[\Swoole\Http\Request::class] ?? null;
            if ($swooleRequest instanceof \Swoole\Http\Request) {
                $psr7Request = $this->converter->createFromSwoole($swooleRequest);
                $context[ServerRequestInterface::class] = $psr7Request; // Cache in the context where raw request was found
                if ($cid !== $currentCid) {
                    /** @var ArrayObject<string, mixed>|null $currentContext */
                    $currentContext = Coroutine::getContext($currentCid);
                    if ($currentContext !== null) {
                        $currentContext[ServerRequestInterface::class] = $psr7Request; // Also cache in current context
                    }
                }

                return $psr7Request;
            }

            $cid = Coroutine::getPcid($cid);
        }

        throw new RequestNotSeededException();
    }

    public function getProtocolVersion(): string
    {
        return $this->getRequest()->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): ServerRequestInterface
    {
        return $this->getRequest()->withProtocolVersion($version); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->getRequest()->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->getRequest()->hasHeader($name);
    }

    /**
     * @return list<string>
     */
    public function getHeader(string $name): array
    {
        return $this->getRequest()->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->getRequest()->getHeaderLine($name);
    }

    /**
     * @param string|list<string> $value
     */
    public function withHeader(string $name, $value): ServerRequestInterface
    {
        return $this->getRequest()->withHeader($name, $value); // @phpstan-ignore return.type
    }

    /**
     * @param string|list<string> $value
     */
    public function withAddedHeader(string $name, $value): ServerRequestInterface
    {
        return $this->getRequest()->withAddedHeader($name, $value); // @phpstan-ignore return.type
    }

    public function withoutHeader(string $name): ServerRequestInterface
    {
        return $this->getRequest()->withoutHeader($name); // @phpstan-ignore return.type
    }

    public function getBody(): StreamInterface
    {
        return $this->getRequest()->getBody();
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        return $this->getRequest()->withBody($body); // @phpstan-ignore return.type
    }

    public function getRequestTarget(): string
    {
        return $this->getRequest()->getRequestTarget();
    }

    public function withRequestTarget(string $requestTarget): ServerRequestInterface
    {
        return $this->getRequest()->withRequestTarget($requestTarget); // @phpstan-ignore return.type
    }

    public function getMethod(): string
    {
        return $this->getRequest()->getMethod();
    }

    public function withMethod(string $method): ServerRequestInterface
    {
        return $this->getRequest()->withMethod($method); // @phpstan-ignore return.type
    }

    public function getUri(): UriInterface
    {
        return $this->getRequest()->getUri();
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): ServerRequestInterface
    {
        return $this->getRequest()->withUri($uri, $preserveHost); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->getRequest()->getServerParams();
    }

    /**
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->getRequest()->getCookieParams();
    }

    /**
     * @param array<string, string> $cookies
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this->getRequest()->withCookieParams($cookies); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->getRequest()->getQueryParams();
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $this->getRequest()->withQueryParams($query); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     */
    public function getUploadedFiles(): array
    {
        return $this->getRequest()->getUploadedFiles();
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $this->getRequest()->withUploadedFiles($uploadedFiles); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>|object|null
     */
    public function getParsedBody()
    {
        return $this->getRequest()->getParsedBody();
    }

    /**
     * @param array<string, mixed>|object|null $data
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        return $this->getRequest()->withParsedBody($data); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->getRequest()->getAttributes();
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null)
    {
        return $this->getRequest()->getAttribute($name, $default);
    }

    public function withAttribute(string $name, mixed $value): ServerRequestInterface
    {
        return $this->getRequest()->withAttribute($name, $value); // @phpstan-ignore return.type
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return $this->getRequest()->withoutAttribute($name); // @phpstan-ignore return.type
    }
}
