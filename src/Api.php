<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Builder\AuthBuilder;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\ErrorBuilder;
use ProgrammatorDev\Api\Builder\HookBuilder;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Builder\PluginBuilder;
use ProgrammatorDev\Api\Builder\ResponseBuilder;
use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Http\Transport;
use ProgrammatorDev\Api\Request\PipelineOptions;
use ProgrammatorDev\Api\Request\RequestOptions;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Response\ResponseDecoder;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

abstract class Api
{
    private ?string $baseUrl = null;

    private array $defaultQueries = [];

    private array $defaultHeaders = [];

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
        $this->clientBuilder = new ClientBuilder();
        $this->authBuilder = new AuthBuilder();
        $this->pluginBuilder = new PluginBuilder();
        $this->responseBuilder = new ResponseBuilder();
        $this->errorBuilder = new ErrorBuilder();
        $this->hookBuilder = new HookBuilder();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function send(
        string $method,
        string $path,
        array $pathParams = [],
        array $query = [],
        array $headers = [],
        string|StreamInterface|null $body = null
    ): Response
    {
        $options = (new RequestOptions())
            ->withQueries($query)
            ->withHeaders($headers)
            ->withBody($body);

        return $this->runtime()->send(
            method: $method,
            path: $path,
            pathParams: $pathParams,
            requestOptions: $options,
            pipelineOptions: new PipelineOptions()
        );
    }

    public function setup(): Setup
    {
        return new Setup(
            // Keep setup helpers protected on Api while exposing them through
            // one explicit SDK-user setup surface.
            fn(string $method, array $arguments): mixed => $this->{$method}(...$arguments)
        );
    }

    /**
     * @template T of Resource
     * @param class-string<T> $class
     * @return T
     */
    protected function resource(string $class): Resource
    {
        return new $class($this->runtime());
    }

    /**
     * @template T of Resource
     * @param class-string<T> $class
     * @return T
     * @todo Merge constructor argument forwarding into resource() in the next major release.
     */
    protected function resourceWith(string $class, mixed ...$arguments): Resource
    {
        return new $class($this->runtime(), ...$arguments);
    }

    protected function baseUrl(?string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    protected function defaultQuery(string $name, mixed $value): static
    {
        $this->defaultQueries[$name] = $value;

        return $this;
    }

    protected function defaultQueries(array $query): static
    {
        $this->defaultQueries = array_merge($this->defaultQueries, $query);

        return $this;
    }

    protected function defaultHeader(string $name, mixed $value): static
    {
        $this->defaultHeaders[$name] = $value;

        return $this;
    }

    protected function defaultHeaders(array $headers): static
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);

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

    protected function hooks(): HookBuilder
    {
        return $this->hookBuilder;
    }

    protected function plugins(): PluginBuilder
    {
        return $this->pluginBuilder;
    }

    protected function cache(CacheItemPoolInterface $pool): CacheBuilder
    {
        $this->cacheBuilder = new CacheBuilder($pool);

        return $this->cacheBuilder;
    }

    protected function client(ClientInterface $client): ClientBuilder
    {
        $this->clientBuilder->client($client);

        return $this->clientBuilder;
    }

    protected function logger(LoggerInterface $logger): LoggerBuilder
    {
        $this->loggerBuilder = new LoggerBuilder($logger);

        return $this->loggerBuilder;
    }

    public function config(array $values = [], array $defaults = []): Config
    {
        if ($defaults !== []) {
            $this->config->merge($defaults);
        }

        if ($values !== []) {
            $this->config->merge($values);
        }

        return $this->config;
    }

    private function runtime(): Runtime
    {
        // Build transport at send time so resources created before later
        // setup() changes still use the latest API configuration.
        $transport = fn(): Transport => new Transport(
            clientBuilder: $this->clientBuilder,
            authBuilder: $this->authBuilder,
            pluginBuilder: $this->pluginBuilder,
            hookBuilder: $this->hookBuilder,
            cacheBuilder: $this->cacheBuilder,
            loggerBuilder: $this->loggerBuilder,
            baseUrl: $this->baseUrl,
            defaultQueries: $this->defaultQueries,
            defaultHeaders: $this->defaultHeaders
        );

        $responseDecoder = new ResponseDecoder($this->responseBuilder);

        return new Runtime(
            config: $this->config,
            transport: $transport,
            responseDecoder: $responseDecoder,
            errorBuilder: $this->errorBuilder
        );
    }
}
