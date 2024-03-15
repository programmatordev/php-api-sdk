<?php

namespace ProgrammatorDev\Api\Builder;

use ProgrammatorDev\Api\Method;
use Psr\Cache\CacheItemPoolInterface;

class CacheBuilder
{
    public function __construct(
        private CacheItemPoolInterface $pool,
        private ?int $ttl = 60,
        private array $methods = [Method::GET, Method::HEAD],
        private array $responseCacheDirectives = ['max-age']
    ) {}

    public function getPool(): CacheItemPoolInterface
    {
        return $this->pool;
    }

    public function setPool(CacheItemPoolInterface $pool): self
    {
        $this->pool = $pool;

        return $this;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function setTtl(?int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function setMethods(array $methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    public function getResponseCacheDirectives(): array
    {
        return $this->responseCacheDirectives;
    }

    public function setResponseCacheDirectives(array $responseCacheDirectives): self
    {
        $this->responseCacheDirectives = $responseCacheDirectives;

        return $this;
    }
}