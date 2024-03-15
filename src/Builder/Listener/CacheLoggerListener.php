<?php

namespace ProgrammatorDev\Api\Builder\Listener;

use Http\Client\Common\Plugin\Cache\Listener\CacheListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use Psr\Cache\CacheItemInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CacheLoggerListener implements CacheListener
{
    public function __construct(private readonly LoggerBuilder $loggerBuilder) {}

    public function onCacheResponse(
        RequestInterface $request,
        ResponseInterface $response,
        $fromCache,
        $cacheItem
    ): ResponseInterface
    {
        $logger = $this->loggerBuilder->getLogger();
        $formatter = $this->loggerBuilder->getFormatter();

        // if response is a cache hit
        if ($fromCache) {
            /** @var $cacheItem CacheItemInterface */
            $logger->info(
                \sprintf("Cache hit:\n%s", $formatter->formatRequest($request)),
                [
                    'expires' => $cacheItem->get()['expiresAt'],
                    'key' => $cacheItem->getKey()
                ]
            );
        }
        // if response is a cache miss (and was cached)
        else if ($cacheItem instanceof CacheItemInterface) {
            // handle future deprecation
            $formattedResponse = \method_exists($formatter, 'formatResponseForRequest')
                ? $formatter->formatResponseForRequest($response, $request)
                : $formatter->formatResponse($response);

            $logger->info(
                \sprintf("Cached response:\n%s", $formattedResponse),
                [
                    'expires' => $cacheItem->get()['expiresAt'],
                    'key' => $cacheItem->getKey()
                ]
            );
        }

        return $response;
    }
}