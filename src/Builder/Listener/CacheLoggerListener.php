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

        if ($fromCache) {
            /** @var $cacheItem CacheItemInterface */
            $logger->info(
                sprintf("HTTP cache hit:\n%s", $formatter->formatRequest($request)),
                [
                    'cache_expires_at' => $this->getExpiresAt($cacheItem),
                    'cache_key' => $cacheItem->getKey()
                ]
            );
        }
        else if ($cacheItem instanceof CacheItemInterface) {
            $formattedResponse = method_exists($formatter, 'formatResponseForRequest')
                ? $formatter->formatResponseForRequest($response, $request)
                : $formatter->formatResponse($response);

            $logger->info(
                sprintf("HTTP response cached:\n%s", $formattedResponse),
                [
                    'cache_expires_at' => $this->getExpiresAt($cacheItem),
                    'cache_key' => $cacheItem->getKey()
                ]
            );
        }

        return $response;
    }

    private function getExpiresAt(CacheItemInterface $cacheItem): mixed
    {
        $data = $cacheItem->get();

        return is_array($data) ? $data['expiresAt'] ?? null : null;
    }
}
