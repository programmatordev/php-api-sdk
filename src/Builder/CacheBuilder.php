<?php

namespace ProgrammatorDev\Api\Builder;

use ProgrammatorDev\Api\Http\Method;
use Psr\Cache\CacheItemPoolInterface;

class CacheBuilder
{
    public function __construct(
        private CacheItemPoolInterface $pool,
        private ?int $defaultTtl = 60,
        private array $methods = [Method::GET, Method::HEAD],
        private array $responseCacheDirectives = ['max-age']
    ) {}

    public function getPool(): CacheItemPoolInterface
    {
        return $this->pool;
    }

    public function pool(CacheItemPoolInterface $pool): self
    {
        $this->pool = $pool;

        return $this;
    }

    public function getDefaultTtl(): ?int
    {
        return $this->defaultTtl;
    }

    public function defaultTtl(?int $defaultTtl): self
    {
        $this->defaultTtl = $defaultTtl;

        return $this;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function methods(array $methods): self
    {
        $this->methods = $methods;

        return $this;
    }

    public function getResponseCacheDirectives(): array
    {
        return $this->responseCacheDirectives;
    }

    public function responseCacheDirectives(array $responseCacheDirectives): self
    {
        $this->responseCacheDirectives = $responseCacheDirectives;

        return $this;
    }
}
