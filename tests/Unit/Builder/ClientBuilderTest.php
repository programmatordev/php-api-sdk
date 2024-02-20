<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use Nyholm\Psr7\Factory\Psr17Factory;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\Psr18Client;

class ClientBuilderTest extends AbstractTestCase
{
    public function testDiscovery()
    {
        $clientBuilder = new ClientBuilder();

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertInstanceOf(RequestFactoryInterface::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $clientBuilder->getStreamFactory());
    }

    public function testDependencyInjection()
    {
        $client = new Psr18Client();
        $requestFactory = $streamFactory = new Psr17Factory();

        $clientBuilder = new ClientBuilder($client, $requestFactory, $streamFactory);

        $this->assertInstanceOf(HttpMethodsClient::class, $clientBuilder->getClient());
        $this->assertInstanceOf(Psr17Factory::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(Psr17Factory::class, $clientBuilder->getStreamFactory());
    }

    public function testSetters()
    {
        $client = new Psr18Client();
        $requestFactory = $streamFactory = new Psr17Factory();

        $clientBuilder = new ClientBuilder();
        $clientBuilder->setClient($client);
        $clientBuilder->setRequestFactory($requestFactory);
        $clientBuilder->setStreamFactory($streamFactory);

        $this->assertInstanceOf(HttpMethodsClient::class, $clientBuilder->getClient());
        $this->assertInstanceOf(Psr17Factory::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(Psr17Factory::class, $clientBuilder->getStreamFactory());
    }

    public function testAddPlugin()
    {
        $clientBuilder = new ClientBuilder();

        $this->assertCount(0, $clientBuilder->getPlugins());

        $clientBuilder->addPlugin(new ContentTypePlugin());
        $clientBuilder->addPlugin(new ContentLengthPlugin());

        $this->assertCount(2, $clientBuilder->getPlugins());
    }
}