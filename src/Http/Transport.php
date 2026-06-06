<?php

namespace ProgrammatorDev\Api\Http;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use ProgrammatorDev\Api\Builder\AuthBuilder;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\HookBuilder;
use ProgrammatorDev\Api\Builder\Listener\CacheLoggerListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Builder\PluginBuilder;
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;
use ProgrammatorDev\Api\Helper\StringHelper;
use ProgrammatorDev\Api\Request\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Transport
{
    private const CONTENT_TYPE_PLUGIN_PRIORITY = 50;
    private const CONTENT_LENGTH_PLUGIN_PRIORITY = 40;
    private const AUTHENTICATION_PLUGIN_PRIORITY = 30;
    private const CACHE_PLUGIN_PRIORITY = 20;
    private const LOGGER_PLUGIN_PRIORITY = 10;

    public function __construct(
        private readonly ClientBuilder $clientBuilder,
        private readonly AuthBuilder $authBuilder,
        private readonly PluginBuilder $pluginBuilder,
        private readonly HookBuilder $hookBuilder,
        private readonly ?CacheBuilder $cacheBuilder = null,
        private readonly ?LoggerBuilder $loggerBuilder = null,
        private readonly ?string $baseUrl = null,
        private readonly array $queryDefaults = [],
        private readonly array $headerDefaults = []
    ) {}

    /**
     * @throws ClientExceptionInterface
     * @throws \UnexpectedValueException
     */
    public function send(
        string $method,
        string $path,
        array $pathParams = [],
        ?RequestOptions $options = null,
        ?Context $context = null
    ): ResponseInterface
    {
        $options ??= new RequestOptions();
        $context ??= new Context();
        $path = $this->buildPath($path, $pathParams);
        $query = $options->getQuery();
        $headers = $options->getHeaders();

        if (!empty($this->queryDefaults)) {
            $query = array_merge($this->queryDefaults, $query);
        }

        if (!empty($this->headerDefaults)) {
            $headers = array_merge($this->headerDefaults, $headers);
        }

        $request = $this->createRequest(
            method: $method,
            url: $this->buildUrl($path, $query),
            headers: $headers,
            body: $options->getBody()
        );

        $request = $this->hookBuilder->applyBeforeRequestHooks(
            new RequestContext($request, $context)
        );

        $response = $this->clientBuilder
            ->getClient($this->buildPlugins())
            ->sendRequest($request);

        return $this->hookBuilder->applyAfterResponseHooks(
            new ResponseContext($request, $response, $context)
        );
    }

    private function buildPlugins(): array
    {
        $plugins = new PluginBuilder();

        $plugins->add(
            plugin: new ContentTypePlugin(),
            priority: self::CONTENT_TYPE_PLUGIN_PRIORITY
        );

        $plugins->add(
            plugin: new ContentLengthPlugin(),
            priority: self::CONTENT_LENGTH_PLUGIN_PRIORITY
        );

        if ($authentication = $this->authBuilder->getAuthentication()) {
            $plugins->add(
                plugin: new AuthenticationPlugin($authentication),
                priority: self::AUTHENTICATION_PLUGIN_PRIORITY
            );
        }

        if ($cachePlugin = $this->buildCachePlugin()) {
            $plugins->add(
                plugin: $cachePlugin,
                priority: self::CACHE_PLUGIN_PRIORITY
            );
        }

        if ($loggerPlugin = $this->buildLoggerPlugin()) {
            $plugins->add(
                plugin: $loggerPlugin,
                priority: self::LOGGER_PLUGIN_PRIORITY
            );
        }

        $plugins->merge($this->pluginBuilder);

        return $plugins->getPlugins();
    }

    private function buildCachePlugin(): ?Plugin
    {
        if ($this->cacheBuilder === null) {
            return null;
        }

        $cacheOptions = [
            'default_ttl' => $this->cacheBuilder->getDefaultTtl(),
            'methods' => $this->cacheBuilder->getMethods(),
            'respect_response_cache_directives' => $this->cacheBuilder->getResponseCacheDirectives(),
            'cache_listeners' => []
        ];

        if ($this->loggerBuilder) {
            $cacheOptions['cache_listeners'][] = new CacheLoggerListener($this->loggerBuilder);
        }

        return new CachePlugin(
            $this->cacheBuilder->getPool(),
            $this->clientBuilder->getStreamFactory(),
            $cacheOptions
        );
    }

    private function buildLoggerPlugin(): ?Plugin
    {
        if ($this->loggerBuilder === null) {
            return null;
        }

        return new LoggerPlugin(
            $this->loggerBuilder->getLogger(),
            $this->loggerBuilder->getFormatter()
        );
    }

    private function buildPath(string $path, array $parameters): string
    {
        foreach ($parameters as $parameter => $value) {
            $path = str_replace(
                sprintf('{%s}', $parameter),
                rawurlencode((string) $value),
                $path
            );
        }

        return $path;
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $query = array_filter($query, static fn(mixed $value): bool => $value !== null);
        $appendQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        if (StringHelper::isUrl($path)) {
            return append_query_string($path, $appendQuery, APPEND_QUERY_STRING_REPLACE_DUPLICATE);
        }

        $url = StringHelper::reduceDuplicateSlashes($this->baseUrl . $path);
        return append_query_string($url, $appendQuery, APPEND_QUERY_STRING_REPLACE_DUPLICATE);
    }

    private function createRequest(
        string $method,
        string $url,
        array $headers = [],
        string|StreamInterface|null $body = null
    ): RequestInterface
    {
        $request = $this->clientBuilder->getRequestFactory()->createRequest($method, $url);

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if ($body !== null && $body !== '') {
            $request = $request->withBody(
                is_string($body) ? $this->clientBuilder->getStreamFactory()->createStream($body) : $body
            );
        }

        return $request;
    }
}
