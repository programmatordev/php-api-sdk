<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Cache\CacheItemPoolInterface;

class CacheBuilderTest extends AbstractTestCase
{
    public function testCacheBuilderUsesDefaults(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);

        $cacheBuilder = new CacheBuilder($pool);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheBuilder->getPool());
        $this->assertSame(60, $cacheBuilder->getDefaultTtl());
        $this->assertSame(['GET', 'HEAD'], $cacheBuilder->getMethods());
        $this->assertSame(['max-age'], $cacheBuilder->getResponseCacheDirectives());
    }

    public function testCacheBuilderAcceptsConstructorValues(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $defaultTtl = 600;
        $methods = ['GET'];
        $responseCacheDirectives = ['no-cache', 'max-age'];

        $cacheBuilder = new CacheBuilder($pool, $defaultTtl, $methods, $responseCacheDirectives);

        $this->assertSame($pool, $cacheBuilder->getPool());
        $this->assertSame($defaultTtl, $cacheBuilder->getDefaultTtl());
        $this->assertSame($methods, $cacheBuilder->getMethods());
        $this->assertSame($responseCacheDirectives, $cacheBuilder->getResponseCacheDirectives());
    }

    public function testCacheBuilderCanBeConfiguredFluently(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $defaultTtl = 600;
        $methods = ['GET'];
        $responseCacheDirectives = ['no-cache', 'max-age'];

        $cacheBuilder = (new CacheBuilder($pool))
            ->pool($pool)
            ->defaultTtl($defaultTtl)
            ->methods($methods)
            ->responseCacheDirectives($responseCacheDirectives);

        $this->assertSame($pool, $cacheBuilder->getPool());
        $this->assertSame($defaultTtl, $cacheBuilder->getDefaultTtl());
        $this->assertSame($methods, $cacheBuilder->getMethods());
        $this->assertSame($responseCacheDirectives, $cacheBuilder->getResponseCacheDirectives());
    }
}
