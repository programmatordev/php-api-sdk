<?php

namespace ProgrammatorDev\Api\Builder;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use ProgrammatorDev\Api\Exception\PluginException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ClientBuilder
{
    /** @var Plugin[] */
    private array $plugins = [];

    public function __construct(
        private ?ClientInterface $client = null,
        private ?RequestFactoryInterface $requestFactory = null,
        private ?StreamFactoryInterface $streamFactory = null
    )
    {
        $this->client ??= Psr18ClientDiscovery::find();
        $this->requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();
    }

    public function getClient(): HttpMethodsClient
    {
        $pluginClientFactory = new PluginClientFactory();
        $client = $pluginClientFactory->createClient($this->client, $this->plugins);

        return new HttpMethodsClient(
            $client,
            $this->requestFactory,
            $this->streamFactory
        );
    }

    public function setClient(ClientInterface $client): self
    {
        $this->client = $client;
        
        return $this;
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        $this->requestFactory = $requestFactory;

        return $this;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        $this->streamFactory = $streamFactory;

        return $this;
    }

    public function addPlugin(Plugin $plugin, int $priority): self
    {
        if (isset($this->plugins[$priority])) {
            throw new PluginException(
                \sprintf('A plugin with priority %d already exists.', $priority)
            );
        }

        $this->plugins[$priority] = $plugin;

        // sort plugins by priority (key) in descending order
        \krsort($this->plugins);

        return $this;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }
}