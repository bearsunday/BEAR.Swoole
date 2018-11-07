<?php

declare(strict_types=1);

namespace BEAR\Swoole;

use BEAR\QueryRepository\ResourceStorageInterface;
use Swoole\Http\Response;

final class HttpCache
{
    /**
     * @var ResourceStorageInterface
     */
    private $storage;

    /**
     * @var Response
     */
    private $response;

    public function __construct(ResourceStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function isNotModified(array $server) : bool
    {
        return isset($server['if-none-match']) && $this->storage->hasEtag($server['if-none-match']);
    }

    /**
     * {@inheritdoc}
     */
    public function transfer(Response $response)
    {
        $response->status(304);
        $response->end('');
    }
}
