<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use Psr\Cache\CacheItemPoolInterface;

class CacheBuilderTest extends AbstractTestCase
{
    public function testDefaults()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);

        $cacheBuilder = new CacheBuilder($pool);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheBuilder->getPool());
        $this->assertSame(60, $cacheBuilder->getTtl());
        $this->assertSame(['GET', 'HEAD'], $cacheBuilder->getMethods());
        $this->assertSame(['no-cache', 'max-age'], $cacheBuilder->getResponseCacheDirectives());
    }

    public function testDependencyInjection()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $ttl = 600;
        $methods = ['GET'];
        $responseCacheDirectives = ['max-age'];

        $cacheBuilder = new CacheBuilder($pool, $ttl, $methods, $responseCacheDirectives);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheBuilder->getPool());
        $this->assertSame($ttl, $cacheBuilder->getTtl());
        $this->assertSame($methods, $cacheBuilder->getMethods());
        $this->assertSame($responseCacheDirectives, $cacheBuilder->getResponseCacheDirectives());
    }

    public function testSetters()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $ttl = 600;
        $methods = ['GET'];
        $responseCacheDirectives = ['max-age'];

        $cacheBuilder = new CacheBuilder($pool);
        $cacheBuilder->setPool($pool);
        $cacheBuilder->setTtl($ttl);
        $cacheBuilder->setMethods($methods);
        $cacheBuilder->setResponseCacheDirectives($responseCacheDirectives);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheBuilder->getPool());
        $this->assertSame($ttl, $cacheBuilder->getTtl());
        $this->assertSame($methods, $cacheBuilder->getMethods());
        $this->assertSame($responseCacheDirectives, $cacheBuilder->getResponseCacheDirectives());
    }
}