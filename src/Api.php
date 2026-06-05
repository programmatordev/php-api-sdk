<?php

namespace ProgrammatorDev\Api;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use ProgrammatorDev\Api\Builder\AuthBuilder;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\ErrorBuilder;
use ProgrammatorDev\Api\Builder\Listener\CacheLoggerListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Builder\PluginBuilder;
use ProgrammatorDev\Api\Builder\ResponseBuilder;
use ProgrammatorDev\Api\Context\ErrorContext;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\PreRequestEvent;
use ProgrammatorDev\Api\Event\ResponseContentsEvent;
use ProgrammatorDev\Api\Helper\StringHelper;
use ProgrammatorDev\Api\Request\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface as ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Api
{
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

    private EventDispatcher $eventDispatcher;

    public function __construct()
    {
        $this->config = new Config();
        $this->clientBuilder ??= new ClientBuilder();
        $this->authBuilder = new AuthBuilder();
        $this->pluginBuilder = new PluginBuilder();
        $this->responseBuilder = new ResponseBuilder();
        $this->errorBuilder = new ErrorBuilder();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * @throws ClientException
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        string|StreamInterface|null $body = null
    ): mixed
    {
        $response = $this->sendRequest($method, $path, $query, $headers, $body);

        // always rewind the body contents in case it was used in the PostRequestEvent
        // otherwise it would return an empty string
        $response->getBody()->rewind();
        $contents = $response->getBody()->getContents();

        // response contents listener
        return $this->eventDispatcher->dispatch(new ResponseContentsEvent($contents))->getContents();
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

        $response = $this->sendRequest(
            method: $method,
            path: $path,
            query: $options->getQuery(),
            headers: $options->getHeaders(),
            body: $options->getBody()
        );

        $context = new Context($this->config);
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
        $this->setBaseUrl($baseUrl);

        return $this;
    }

    protected function queryDefaults(array $query): static
    {
        foreach ($query as $name => $value) {
            $this->addQueryDefault($name, $value);
        }

        return $this;
    }

    protected function headerDefaults(array $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->addHeaderDefault($name, $value);
        }

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

    public function plugins(): PluginBuilder
    {
        return $this->pluginBuilder;
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

        // https://docs.php-http.org/en/latest/plugins/content-type.html
        $plugins->add(
            plugin: new ContentTypePlugin(),
            priority: 40
        );

        // https://docs.php-http.org/en/latest/plugins/content-length.html
        $plugins->add(
            plugin: new ContentLengthPlugin(),
            priority: 32
        );

        // https://docs.php-http.org/en/latest/message/authentication.html
        if ($authentication = $this->authBuilder->authentication()) {
            $plugins->add(
                plugin: new AuthenticationPlugin($authentication),
                priority: 24
            );
        }

        // https://docs.php-http.org/en/latest/plugins/cache.html
        if ($this->cacheBuilder) {
            $cacheOptions = [
                'default_ttl' => $this->cacheBuilder->getTtl(),
                'methods' => $this->cacheBuilder->getMethods(),
                'respect_response_cache_directives' => $this->cacheBuilder->getResponseCacheDirectives(),
                'cache_listeners' => []
            ];

            if ($this->loggerBuilder) {
                $cacheOptions['cache_listeners'][] = new CacheLoggerListener($this->loggerBuilder);
            }

            $plugins->add(
                plugin: new CachePlugin(
                    $this->cacheBuilder->getPool(),
                    $this->clientBuilder->getStreamFactory(),
                    $cacheOptions
                ),
                priority: 16
            );
        }

        // https://docs.php-http.org/en/latest/plugins/logger.html
        if ($this->loggerBuilder) {
            $plugins->add(
                plugin: new LoggerPlugin(
                    $this->loggerBuilder->getLogger(),
                    $this->loggerBuilder->getFormatter()
                ),
                priority: 8
            );
        }

        $plugins
            ->merge($this->clientBuilder->getPluginBuilder())
            ->merge($this->pluginBuilder);

        return $plugins->all();
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getQueryDefault(string $name): mixed
    {
        return $this->queryDefaults[$name] ?? null;
    }

    public function addQueryDefault(string $name, mixed $value): self
    {
        $this->queryDefaults[$name] = $value;

        return $this;
    }

    public function removeQueryDefault(string $name): self
    {
        unset($this->queryDefaults[$name]);

        return $this;
    }

    public function getHeaderDefault(string $name): mixed
    {
        return $this->headerDefaults[$name] ?? null;
    }

    public function addHeaderDefault(string $name, mixed $value): self
    {
        $this->headerDefaults[$name] = $value;

        return $this;
    }

    public function removeHeaderDefault(string $name): self
    {
        unset($this->headerDefaults[$name]);

        return $this;
    }

    public function getClientBuilder(): ?ClientBuilder
    {
        return $this->clientBuilder;
    }

    public function setClientBuilder(ClientBuilder $clientBuilder): self
    {
        $this->clientBuilder = $clientBuilder;

        return $this;
    }

    public function getCacheBuilder(): ?CacheBuilder
    {
        return $this->cacheBuilder;
    }

    public function setCacheBuilder(?CacheBuilder $cacheBuilder): self
    {
        $this->cacheBuilder = $cacheBuilder;

        return $this;
    }

    public function getLoggerBuilder(): ?LoggerBuilder
    {
        return $this->loggerBuilder;
    }

    public function setLoggerBuilder(?LoggerBuilder $loggerBuilder): self
    {
        $this->loggerBuilder = $loggerBuilder;

        return $this;
    }

    public function addPreRequestListener(callable $listener, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(PreRequestEvent::class, $listener, $priority);

        return $this;
    }

    public function addPostRequestListener(callable $listener, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(PostRequestEvent::class, $listener, $priority);

        return $this;
    }

    public function addResponseContentsListener(callable $listener, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(ResponseContentsEvent::class, $listener, $priority);

        return $this;
    }

    public function buildPath(string $path, array $parameters): string
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
        string|StreamInterface|null $body = null
    ): ResponseInterface
    {
        if (!empty($this->queryDefaults)) {
            $query = array_merge($this->queryDefaults, $query);
        }

        if (!empty($this->headerDefaults)) {
            $headers = array_merge($this->headerDefaults, $headers);
        }

        $url = $this->buildUrl($path, $query);
        $request = $this->createRequest($method, $url, $headers, $body);
        $plugins = $this->buildPlugins();

        // pre request listener
        $request = $this->eventDispatcher->dispatch(new PreRequestEvent($request))->getRequest();

        // request
        $response = $this->clientBuilder->getClient($plugins)->sendRequest($request);

        // post request listener
        return $this->eventDispatcher->dispatch(new PostRequestEvent($request, $response))->getResponse();
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
