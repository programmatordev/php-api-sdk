<?php

namespace ProgrammatorDev\Api\Client;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
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
        $this->addPlugin(new Plugin\ContentTypePlugin());
        $this->addPlugin(new Plugin\ContentLengthPlugin());

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

    public function addPlugin(Plugin $plugin): void
    {
        $this->plugins[] = $plugin;
    }
}