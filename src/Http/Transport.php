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
use ProgrammatorDev\Api\Helper\UrlHelper;
use ProgrammatorDev\Api\Request\PipelineOption;
use ProgrammatorDev\Api\Request\PipelineOptions;
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
        private readonly array $defaultQueries = [],
        private readonly array $defaultHeaders = []
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
        ?PipelineOptions $pipelineOptions = null,
        ?Context $context = null
    ): ResponseInterface
    {
        $options ??= new RequestOptions();
        $pipelineOptions ??= new PipelineOptions();
        $context ??= new Context();
        $path = $this->buildPath($path, $pathParams);
        $query = $options->getQuery();
        $headers = $options->getHeaders();

        if (!empty($this->defaultQueries)) {
            $query = array_merge($this->defaultQueries, $query);
        }

        if (!empty($this->defaultHeaders)) {
            $headers = array_merge($this->defaultHeaders, $headers);
        }

        // Normalize after merging so API defaults and endpoint values
        // follow the same rules before request serialization.
        $query = $this->normalizeBackedEnums($query);
        // PSR-7 requires header values to be strings,
        // including values from integer-backed enums.
        $headers = $this->normalizeBackedEnums($headers, stringify: true);

        $request = $this->createRequest(
            method: $method,
            url: $this->buildUrl($path, $query, $options->shouldPreserveUrlQuery()),
            headers: $headers,
            body: $options->getBody()
        );

        $request = $this->hookBuilder->applyBeforeRequestHooks(
            new RequestContext($request, $context)
        );

        $response = $this->clientBuilder
            ->getClient($this->buildPlugins($pipelineOptions))
            ->sendRequest($request);

        return $this->hookBuilder->applyAfterResponseHooks(
            new ResponseContext($request, $response, $context)
        );
    }

    private function buildPlugins(PipelineOptions $pipelineOptions): array
    {
        $plugins = new PluginBuilder();

        // Internal plugins are registered before user plugins
        // so custom plugins can still run before, between, or after them by choosing a priority.
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

        if ($cachePlugin = $this->buildCachePlugin($pipelineOptions)) {
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

    private function buildCachePlugin(PipelineOptions $pipelineOptions): ?Plugin
    {
        if ($this->cacheBuilder === null) {
            if ($pipelineOptions->has(PipelineOption::CACHE)) {
                throw new \RuntimeException('Endpoint cache overrides require API-level cache configuration.');
            }

            return null;
        }

        // Request-local pipeline options adjust a clone so endpoint defaults and resource overrides
        // do not leak into the API-level cache configuration.
        $cacheBuilder = clone $this->cacheBuilder;
        $pipelineOptions->applyTo(PipelineOption::CACHE, $cacheBuilder);

        $cacheOptions = [
            'default_ttl' => $cacheBuilder->getDefaultTtl(),
            'methods' => $cacheBuilder->getMethods(),
            'respect_response_cache_directives' => $cacheBuilder->getResponseCacheDirectives(),
            'cache_listeners' => []
        ];

        if ($this->loggerBuilder) {
            $cacheOptions['cache_listeners'][] = new CacheLoggerListener($this->loggerBuilder);
        }

        return new CachePlugin(
            $cacheBuilder->getPool(),
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

    /**
     * @param bool $stringify Convert backed values to strings for PSR-7 headers.
     */
    private function normalizeBackedEnums(mixed $value, bool $stringify = false): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $stringify ? (string) $value->value : $value->value;
        }

        if (!is_array($value)) {
            return $value;
        }

        // Query structures can be nested and header values can be lists.
        return array_map(
            fn(mixed $item): mixed => $this->normalizeBackedEnums($item, $stringify),
            $value
        );
    }

    private function buildUrl(string $path, array $query = [], bool $preserveUrlQuery = false): string
    {
        $query = array_filter($query, static fn(mixed $value): bool => $value !== null);
        $appendQuery = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $url = UrlHelper::join($this->baseUrl, $path);

        // A preserved URL query is authoritative. For example, with defaults
        // `page=1&locale=en`, requesting `/users?page=2` must produce
        // `/users?page=2&locale=en`. Skipping duplicate defaults also retains repeated values
        // such as `tag=a&tag=b` and keys such as `filter.name` without reparsing the URL.
        return append_query_string(
            $url,
            $appendQuery,
            $preserveUrlQuery
                ? APPEND_QUERY_STRING_SKIP_DUPLICATE
                : APPEND_QUERY_STRING_REPLACE_DUPLICATE
        );
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
