<?php

namespace ProgrammatorDev\Api;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Message\Authentication;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\Listener\CacheLoggerListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\ResponseContentsEvent;
use ProgrammatorDev\Api\Exception\ConfigException;
use ProgrammatorDev\Api\Helper\StringHelperTrait;
use Psr\Http\Client\ClientExceptionInterface as ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Api
{
    use StringHelperTrait;

    private ?string $baseUrl = null;

    private array $queryDefaults = [];

    private array $headerDefaults = [];

    private ClientBuilder $clientBuilder;

    private ?CacheBuilder $cacheBuilder = null;

    private ?LoggerBuilder $loggerBuilder = null;

    private ?Authentication $authentication = null;

    private EventDispatcher $eventDispatcher;

    private OptionsResolver $optionsResolver;

    public function __construct()
    {
        $this->clientBuilder ??= new ClientBuilder();
        $this->eventDispatcher = new EventDispatcher();
        $this->optionsResolver = new OptionsResolver();
    }

    /**
     * @throws ConfigException If a base URL has not been set.
     * @throws ClientException
     */
    protected function request(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        string|StreamInterface $body = null
    ): mixed
    {
        if (!$this->baseUrl) {
            throw new ConfigException('A base URL must be set.');
        }

        if (!empty($this->queryDefaults)) {
            $query = \array_merge($this->queryDefaults, $query);
        }

        if (!empty($this->headerDefaults)) {
            $headers = \array_merge($this->headerDefaults, $headers);
        }

        $this->configurePlugins();

        $uri = $this->buildUri($path, $query);
        $request = $this->buildRequest($method, $uri, $headers, $body);
        $response = $this->clientBuilder->getClient()->sendRequest($request);

        $this->eventDispatcher->dispatch(new PostRequestEvent($request, $response));

        $contents = $response->getBody()->getContents();
        return $this->eventDispatcher->dispatch(new ResponseContentsEvent($contents))->getContents();
    }

    private function configurePlugins(): void
    {
        // https://docs.php-http.org/en/latest/plugins/content-type.html
        $this->clientBuilder->addPlugin(new ContentTypePlugin(), 40);

        // https://docs.php-http.org/en/latest/plugins/content-length.html
        $this->clientBuilder->addPlugin(new ContentLengthPlugin(), 32);

        // https://docs.php-http.org/en/latest/message/authentication.html
        if ($this->authentication) {
            $this->clientBuilder->addPlugin(
                new AuthenticationPlugin($this->authentication),
                24
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

            $this->clientBuilder->addPlugin(
                new CachePlugin(
                    $this->cacheBuilder->getPool(),
                    $this->clientBuilder->getStreamFactory(),
                    $cacheOptions
                ),
                16
            );
        }

        // https://docs.php-http.org/en/latest/plugins/logger.html
        if ($this->loggerBuilder) {
            $this->clientBuilder->addPlugin(
                new LoggerPlugin(
                    $this->loggerBuilder->getLogger(),
                    $this->loggerBuilder->getFormatter()
                ),
                8
            );
        }
    }

    protected function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    protected function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    protected function getQueryDefault(string $name): mixed
    {
        return $this->queryDefaults[$name] ?? null;
    }

    protected function addQueryDefault(string $name, mixed $value): self
    {
        $this->queryDefaults[$name] = $value;

        return $this;
    }

    protected function removeQueryDefault(string $name): self
    {
        unset($this->queryDefaults[$name]);

        return $this;
    }

    protected function getHeaderDefault(string $name): mixed
    {
        return $this->headerDefaults[$name] ?? null;
    }

    protected function addHeaderDefault(string $name, mixed $value): self
    {
        $this->headerDefaults[$name] = $value;

        return $this;
    }

    protected function removeHeaderDefault(string $name): self
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

    protected function getAuthentication(): ?Authentication
    {
        return $this->authentication;
    }

    protected function setAuthentication(?Authentication $authentication): self
    {
        $this->authentication = $authentication;

        return $this;
    }

    protected function addPostRequestHandler(callable $handler, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(PostRequestEvent::class, $handler, $priority);

        return $this;
    }

    protected function addResponseContentsHandler(callable $handler, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(ResponseContentsEvent::class, $handler, $priority);

        return $this;
    }

    protected function buildPath(string $path, array $parameters): string
    {
        foreach ($parameters as $parameter => $value) {
            $path = \str_replace(
                \sprintf('{%s}', $parameter),
                $value,
                $path
            );
        }

        return $path;
    }

    private function buildUri(string $path, array $query = []): string
    {
        $uri = $this->reduceDuplicateSlashes($this->baseUrl . $path);

        if (!empty($query)) {
            $uri = \sprintf('%s?%s', $uri, \http_build_query($query));
        }

        return $uri;
    }

    private function buildRequest(
        string $method,
        string $uri,
        array $headers = [],
        string|StreamInterface $body = null
    ): RequestInterface
    {
        $request = $this->clientBuilder->getRequestFactory()->createRequest($method, $uri);

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if ($body !== null && $body !== '') {
            $request = $request->withBody(
                \is_string($body) ? $this->clientBuilder->getStreamFactory()->createStream($body) : $body
            );
        }

        return $request;
    }
}