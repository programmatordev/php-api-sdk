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
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Context\ErrorContext;
use ProgrammatorDev\Api\Http\Transport;
use ProgrammatorDev\Api\Request\RequestOptions;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Response\ResponseDecoder;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

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
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function send(
        string $method,
        string $path,
        array $pathParams = [],
        ?RequestOptions $options = null
    ): Response
    {
        $options ??= new RequestOptions();
        $context = new Context($this->config);

        $response = $this->transport()->send(
            method: $method,
            path: $path,
            pathParams: $pathParams,
            options: $options,
            context: $context
        );

        $apiResponse = new Response(
            data: $this->responseDecoder()->decode($response),
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

    private function transport(): Transport
    {
        return new Transport(
            clientBuilder: $this->clientBuilder,
            authBuilder: $this->authBuilder,
            pluginBuilder: $this->pluginBuilder,
            hookBuilder: $this->hookBuilder,
            cacheBuilder: $this->cacheBuilder,
            loggerBuilder: $this->loggerBuilder,
            baseUrl: $this->baseUrl,
            queryDefaults: $this->queryDefaults,
            headerDefaults: $this->headerDefaults
        );
    }

    private function responseDecoder(): ResponseDecoder
    {
        return new ResponseDecoder($this->responseBuilder);
    }
}
