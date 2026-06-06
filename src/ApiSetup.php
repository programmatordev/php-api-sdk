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
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class ApiSetup
{
    /**
     * @param \Closure(string, array): mixed $call
     */
    public function __construct(
        private readonly \Closure $call
    ) {}

    public function baseUrl(?string $baseUrl): self
    {
        $this->call('baseUrl', [$baseUrl]);

        return $this;
    }

    public function defaultQuery(string $name, mixed $value): self
    {
        $this->call('defaultQuery', [$name, $value]);

        return $this;
    }

    public function defaultQueries(array $query): self
    {
        $this->call('defaultQueries', [$query]);

        return $this;
    }

    public function defaultHeader(string $name, mixed $value): self
    {
        $this->call('defaultHeader', [$name, $value]);

        return $this;
    }

    public function defaultHeaders(array $headers): self
    {
        $this->call('defaultHeaders', [$headers]);

        return $this;
    }

    public function responses(): ResponseBuilder
    {
        return $this->call('responses');
    }

    public function errors(): ErrorBuilder
    {
        return $this->call('errors');
    }

    public function auth(): AuthBuilder
    {
        return $this->call('auth');
    }

    public function hooks(): HookBuilder
    {
        return $this->call('hooks');
    }

    public function plugins(): PluginBuilder
    {
        return $this->call('plugins');
    }

    public function cache(CacheItemPoolInterface $pool): CacheBuilder
    {
        return $this->call('cache', [$pool]);
    }

    public function client(ClientInterface $client): ClientBuilder
    {
        return $this->call('client', [$client]);
    }

    public function logger(LoggerInterface $logger): LoggerBuilder
    {
        return $this->call('logger', [$logger]);
    }

    public function config(?array $values = null): Config
    {
        return $this->call('config', [$values]);
    }

    private function call(string $method, array $arguments = []): mixed
    {
        return ($this->call)($method, $arguments);
    }
}
