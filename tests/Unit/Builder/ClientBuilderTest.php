<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Client\Common\Plugin;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ClientBuilderTest extends AbstractTestCase
{
    public function testDefaults()
    {
        $clientBuilder = new ClientBuilder();

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertInstanceOf(RequestFactoryInterface::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $clientBuilder->getStreamFactory());
    }

    public function testDependencyInjection()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $clientBuilder = new ClientBuilder($client, $requestFactory, $streamFactory);

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertInstanceOf(RequestFactoryInterface::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $clientBuilder->getStreamFactory());
    }

    public function testSetters()
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $clientBuilder = new ClientBuilder();
        $clientBuilder->setClient($client);
        $clientBuilder->setRequestFactory($requestFactory);
        $clientBuilder->setStreamFactory($streamFactory);

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertInstanceOf(RequestFactoryInterface::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $clientBuilder->getStreamFactory());
    }

    public function testAddPlugin()
    {
        $low = $this->createMock(Plugin::class);
        $high = $this->createMock(Plugin::class);
        $middle = $this->createMock(Plugin::class);
        $clientBuilder = new ClientBuilder();

        $clientBuilder->addPlugin($low, 1);
        $clientBuilder->addPlugin($high, 3);
        $clientBuilder->addPlugin($middle, 2);

        $this->assertCount(3, $clientBuilder->getPlugins());
        $this->assertSame([$high, $middle, $low], $clientBuilder->getPlugins());
    }

    public function testAddPluginWithSamePriority()
    {
        $plugin = $this->createMock(Plugin::class);
        $clientBuilder = new ClientBuilder();

        $clientBuilder->addPlugin($plugin, 1);
        $clientBuilder->addPlugin($plugin, 1);

        $this->assertCount(2, $clientBuilder->getPlugins());
    }
}
