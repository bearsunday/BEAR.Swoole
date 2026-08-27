<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use ArrayObject;
use BEAR\Swoole\Exception\RequestNotSeededException;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Coroutine;
use Swoole\Http\Request;

final readonly class SwooleRequestProxy implements ServerRequestInterface
{
    public function __construct(
        private SwooleServerRequestConverter $converter,
    ) {
    }

    private function getRequest(): ServerRequestInterface
    {
        $psr7Request = CoroutineContextFinder::find(ServerRequestInterface::class, ServerRequestInterface::class);
        if ($psr7Request !== null) {
            return $psr7Request;
        }

        $swooleRequest = CoroutineContextFinder::find(Request::class, Request::class);
        if ($swooleRequest !== null) {
            return $this->convertAndCache($swooleRequest);
        }

        throw new RequestNotSeededException(); // @codeCoverageIgnore
    }

    /** @codeCoverageIgnore */
    private function convertAndCache(Request $swooleRequest): ServerRequestInterface
    {
        $psr7Request = $this->converter->createFromSwoole($swooleRequest);

        $cid = Coroutine::getCid();
        if (is_int($cid) && $cid !== -1) {
            /** @var ArrayObject<string, mixed>|null $context */
            $context = Coroutine::getContext($cid);
            if ($context !== null) {
                $context[ServerRequestInterface::class] = $psr7Request;
            }
        }

        return $psr7Request;
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getProtocolVersion(): string
    {
        return $this->getRequest()->getProtocolVersion();
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withProtocolVersion(string $version): ServerRequestInterface
    {
        return $this->getRequest()->withProtocolVersion($version); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, list<string>>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getHeaders(): array
    {
        return $this->getRequest()->getHeaders();
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function hasHeader(string $name): bool
    {
        return $this->getRequest()->hasHeader($name);
    }

    /**
     * @return list<string>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getHeader(string $name): array
    {
        return $this->getRequest()->getHeader($name);
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getHeaderLine(string $name): string
    {
        return $this->getRequest()->getHeaderLine($name);
    }

    /**
     * @param string|list<string> $value
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withHeader(string $name, $value): ServerRequestInterface
    {
        return $this->getRequest()->withHeader($name, $value); // @phpstan-ignore return.type
    }

    /**
     * @param string|list<string> $value
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withAddedHeader(string $name, $value): ServerRequestInterface
    {
        return $this->getRequest()->withAddedHeader($name, $value); // @phpstan-ignore return.type
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withoutHeader(string $name): ServerRequestInterface
    {
        return $this->getRequest()->withoutHeader($name); // @phpstan-ignore return.type
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getBody(): StreamInterface
    {
        return $this->getRequest()->getBody();
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        return $this->getRequest()->withBody($body); // @phpstan-ignore return.type
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getRequestTarget(): string
    {
        return $this->getRequest()->getRequestTarget();
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withRequestTarget(string $requestTarget): ServerRequestInterface
    {
        return $this->getRequest()->withRequestTarget($requestTarget); // @phpstan-ignore return.type
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getMethod(): string
    {
        return $this->getRequest()->getMethod();
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withMethod(string $method): ServerRequestInterface
    {
        return $this->getRequest()->withMethod($method); // @phpstan-ignore return.type
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function getUri(): UriInterface
    {
        return $this->getRequest()->getUri();
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withUri(UriInterface $uri, bool $preserveHost = false): ServerRequestInterface
    {
        return $this->getRequest()->withUri($uri, $preserveHost); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getServerParams(): array
    {
        return $this->getRequest()->getServerParams();
    }

    /**
     * @return array<string, string>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getCookieParams(): array
    {
        return $this->getRequest()->getCookieParams();
    }

    /**
     * @param array<string, string> $cookies
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this->getRequest()->withCookieParams($cookies); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getQueryParams(): array
    {
        return $this->getRequest()->getQueryParams();
    }

    /**
     * @param array<string, mixed> $query
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $this->getRequest()->withQueryParams($query); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getUploadedFiles(): array
    {
        return $this->getRequest()->getUploadedFiles();
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $this->getRequest()->withUploadedFiles($uploadedFiles); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>|object|null
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getParsedBody()
    {
        return $this->getRequest()->getParsedBody();
    }

    /**
     * @param array<string, mixed>|object|null $data
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function withParsedBody($data): ServerRequestInterface
    {
        return $this->getRequest()->withParsedBody($data); // @phpstan-ignore return.type
    }

    /**
     * @return array<string, mixed>
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getAttributes(): array
    {
        return $this->getRequest()->getAttributes();
    }

    /**
     * @return mixed
     *
     * @codeCoverageIgnore
     */
    #[Override]
    public function getAttribute(string $name, mixed $default = null)
    {
        return $this->getRequest()->getAttribute($name, $default);
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withAttribute(string $name, mixed $value): ServerRequestInterface
    {
        return $this->getRequest()->withAttribute($name, $value); // @phpstan-ignore return.type
    }

    /** @codeCoverageIgnore */
    #[Override]
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return $this->getRequest()->withoutAttribute($name); // @phpstan-ignore return.type
    }
}
