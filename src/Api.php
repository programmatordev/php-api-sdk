<?php

namespace ProgrammatorDev\Api;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use ProgrammatorDev\Api\Builder\AuthBuilder;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\ErrorBuilder;
use ProgrammatorDev\Api\Builder\HookBuilder;
use ProgrammatorDev\Api\Builder\Listener\CacheLoggerListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Builder\PluginBuilder;
use ProgrammatorDev\Api\Builder\ResponseBuilder;
use ProgrammatorDev\Api\Context\ErrorContext;
use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;
use ProgrammatorDev\Api\Helper\StringHelper;
use ProgrammatorDev\Api\Request\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface as ClientException;
use Psr\Http\Client\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class Api
{
    private const CONTENT_TYPE_PLUGIN_PRIORITY = 50;
    private const CONTENT_LENGTH_PLUGIN_PRIORITY = 40;
    private const AUTHENTICATION_PLUGIN_PRIORITY = 30;
    private const CACHE_PLUGIN_PRIORITY = 20;
    private const LOGGER_PLUGIN_PRIORITY = 10;

    private ?string $baseUrl = null;

    private array $queryDefaults = [];

    private array $headerDefaults = [];

    private Config $config;

    private ClientBuilder $clientBuilder;

    private ?CacheBuilder $cacheBuilder = null;

    private ?LoggerBuilder $loggerBuilder = null;

    private AuthBuilder $authBuilder;

    private PluginBuilder $pluginBuilder;

    private ResponseBuilder $responseBuilder;

    private ErrorBuilder $errorBuilder;

    private HookBuilder $hookBuilder;

    public function __construct()
    {
        $this->config = new Config();
        $this->clientBuilder ??= new ClientBuilder();
        $this->authBuilder = new AuthBuilder();
        $this->pluginBuilder = new PluginBuilder();
        $this->responseBuilder = new ResponseBuilder();
        $this->errorBuilder = new ErrorBuilder();
        $this->hookBuilder = new HookBuilder();
    }

    /**
     * @internal
     * @throws ClientException
     */
    public function send(
        string $method,
        string $path,
        array $pathParams = [],
        ?RequestOptions $options = null
    ): Response
    {
        $options ??= new RequestOptions();
        $path = $this->buildPath($path, $pathParams);
        $context = new Context($this->config);

        $response = $this->sendRequest(
            method: $method,
            path: $path,
            query: $options->getQuery(),
            headers: $options->getHeaders(),
            body: $options->getBody(),
            options: $options,
            context: $context
        );

        $apiResponse = new Response(
            data: $this->getResponseData($response),
            rawResponse: $response,
            context: $context
        );

        $this->errorBuilder->throwIfMatched(new ErrorContext($apiResponse, $context));

        return $apiResponse;
    }

    /**
     * @template T of Resource
     * @param class-string<T> $class
     * @return T
     */
    protected function resource(string $class): Resource
    {
        return new $class($this);
    }

    protected function baseUrl(?string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    protected function queryDefaults(array $query): static
    {
        $this->queryDefaults = array_merge($this->queryDefaults, $query);

        return $this;
    }

    protected function headerDefaults(array $headers): static
    {
        $this->headerDefaults = array_merge($this->headerDefaults, $headers);

        return $this;
    }

    protected function responses(): ResponseBuilder
    {
        return $this->responseBuilder;
    }

    protected function errors(): ErrorBuilder
    {
        return $this->errorBuilder;
    }

    protected function auth(): AuthBuilder
    {
        return $this->authBuilder;
    }

    public function hooks(): HookBuilder
    {
        return $this->hookBuilder;
    }

    public function plugins(): PluginBuilder
    {
        return $this->pluginBuilder;
    }

    public function cache(CacheItemPoolInterface $pool): CacheBuilder
    {
        $this->cacheBuilder = new CacheBuilder($pool);

        return $this->cacheBuilder;
    }

    public function client(ClientInterface $client): ClientBuilder
    {
        $this->clientBuilder->client($client);

        return $this->clientBuilder;
    }

    public function logger(LoggerInterface $logger): LoggerBuilder
    {
        $this->loggerBuilder = new LoggerBuilder($logger);

        return $this->loggerBuilder;
    }

    public function config(?array $values = null): Config
    {
        if ($values !== null) {
            $this->config->merge($values);
        }

        return $this->config;
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

        if ($authentication = $this->authBuilder->authentication()) {
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

        return $plugins->all();
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

    /**
     * @throws ClientException
     */
    private function sendRequest(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        string|StreamInterface|null $body = null,
        ?RequestOptions $options = null,
        ?Context $context = null
    ): ResponseInterface
    {
        $context ??= new Context($this->config);

        if (!empty($this->queryDefaults)) {
            $query = array_merge($this->queryDefaults, $query);
        }

        if (!empty($this->headerDefaults)) {
            $headers = array_merge($this->headerDefaults, $headers);
        }

        $url = $this->buildUrl($path, $query);
        $request = $this->createRequest($method, $url, $headers, $body);
        $plugins = $this->buildPlugins();

        $request = $this->hookBuilder->applyBeforeRequestHooks(
            new RequestContext($request, $context)
        );

        $response = $this->clientBuilder->getClient($plugins)->sendRequest($request);

        $response = $this->hookBuilder->applyAfterResponseHooks(
            new ResponseContext($request, $response, $context)
        );

        return $response;
    }

    private function getResponseData(ResponseInterface $response): mixed
    {
        $response->getBody()->rewind();
        $contents = $response->getBody()->getContents();

        if (!$this->responseBuilder->shouldDecodeJson()) {
            return $contents;
        }

        if ($contents === '') {
            return null;
        }

        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }
}
