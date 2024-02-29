<?php

namespace ProgrammatorDev\Api;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Client\Common\Plugin\QueryDefaultsPlugin;
use Http\Client\Exception;
use Http\Message\Authentication;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\Listener\CacheLoggerListener;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\ResponseEvent;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Helper\StringHelperTrait;
use ProgrammatorDev\YetAnotherPhpValidator\Exception\ValidationException;
use ProgrammatorDev\YetAnotherPhpValidator\Validator;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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

    public function __construct()
    {
        $this->clientBuilder ??= new ClientBuilder();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * @throws MissingConfigException
     * @throws Exception
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
            throw new MissingConfigException('A base URL must be set.');
        }

        // help servers understand the content
        $this->clientBuilder->addPlugin(new ContentTypePlugin());
        $this->clientBuilder->addPlugin(new ContentLengthPlugin());

        // merge request query values with query defaults
        // request query values should overwrite query defaults
        if (!empty($this->queryDefaults)) {
            $query = array_merge($this->queryDefaults, $query);
        }

        // https://docs.php-http.org/en/latest/plugins/query.html
        if (!empty($query)) {
            $this->clientBuilder->addPlugin(
                new QueryDefaultsPlugin($query)
            );
        }

        // merge request header values with header defaults
        // request header values should overwrite header defaults
        if (!empty($this->headerDefaults)) {
            $headers = array_merge($this->headerDefaults, $headers);
        }

        // https://docs.php-http.org/en/latest/plugins/headers.html
        if (!empty($headers)) {
            $this->clientBuilder->addPlugin(
                new HeaderDefaultsPlugin($headers)
            );
        }

        // https://docs.php-http.org/en/latest/message/authentication.html
        if ($this->authentication) {
            $this->clientBuilder->addPlugin(
                new AuthenticationPlugin($this->authentication)
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
                )
            );
        }

        // https://docs.php-http.org/en/latest/plugins/logger.html
        if ($this->loggerBuilder) {
            $this->clientBuilder->addPlugin(
                new LoggerPlugin(
                    $this->loggerBuilder->getLogger(),
                    $this->loggerBuilder->getFormatter()
                )
            );
        }

        $response = $this->clientBuilder->getClient()->send(
            $method,
            $this->createUri($path),
            $headers,
            $body
        );

        $this->eventDispatcher->dispatch(new PostRequestEvent($response));

        $contents = $response->getBody()->getContents();

        return $this->eventDispatcher->dispatch(new ResponseEvent($contents))->getContents();
    }

//    private function createRequest(
//        string $method,
//        string $uri,
//        array $headers = [],
//        string|StreamInterface $body = null
//    ): RequestInterface
//    {
//        $request = $this->clientBuilder->getRequestFactory()->createRequest($method, $uri);
//
//        foreach ($headers as $key => $value) {
//            $request = $request->withHeader($key, $value);
//        }
//
//        if ($body !== null && $body !== '') {
//            $request = $request->withBody(
//                \is_string($body) ? $this->clientBuilder->getStreamFactory()->createStream($body) : $body
//            );
//        }
//
//        return $request;
//    }

    protected function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * @throws ValidationException
     */
    protected function setBaseUrl(string $baseUrl): self
    {
        Validator::url()->assert($baseUrl);

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

    protected function addResponseHandler(callable $handler, int $priority = 0): self
    {
        $this->eventDispatcher->addListener(ResponseEvent::class, $handler, $priority);

        return $this;
    }

    private function createUri(string $path): string
    {
        return $this->reduceDuplicateSlashes($this->getBaseUrl() . $path);
    }
}