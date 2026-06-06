<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder\Listener;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Builder\Listener\CacheLoggerListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;

class CacheLoggerListenerTest extends AbstractTestCase
{
    public function testCacheHitIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $cacheItem = $this->cacheItem();

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->stringStartsWith('HTTP cache hit:'),
                [
                    'cache_expires_at' => 1234567890,
                    'cache_key' => 'cache-key',
                ]
            );

        $listener = new CacheLoggerListener(new LoggerBuilder($logger));

        $listener->onCacheResponse(
            new Request('GET', 'https://api.example.com/users'),
            new Response(body: '{}'),
            true,
            $cacheItem
        );
    }

    public function testCachedResponseIsLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $cacheItem = $this->cacheItem();

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->stringStartsWith('HTTP response cached:'),
                [
                    'cache_expires_at' => 1234567890,
                    'cache_key' => 'cache-key',
                ]
            );

        $listener = new CacheLoggerListener(new LoggerBuilder($logger));

        $listener->onCacheResponse(
            new Request('GET', 'https://api.example.com/users'),
            new Response(body: '{}'),
            false,
            $cacheItem
        );
    }

    public function testCacheMissWithoutStoredResponseIsNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->never())
            ->method('info');

        $listener = new CacheLoggerListener(new LoggerBuilder($logger));

        $listener->onCacheResponse(
            new Request('GET', 'https://api.example.com/users'),
            new Response(body: '{}'),
            false,
            null
        );
    }

    private function cacheItem(): CacheItemInterface
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem
            ->method('get')
            ->willReturn(['expiresAt' => 1234567890]);

        $cacheItem
            ->method('getKey')
            ->willReturn('cache-key');

        return $cacheItem;
    }
}
