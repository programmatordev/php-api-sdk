<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Client\Common\Plugin;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Exception\PluginException;
use ProgrammatorDev\Api\Test\AbstractTestCase;
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
        $plugin = $this->createMock(Plugin::class);
        $clientBuilder = new ClientBuilder();

        $clientBuilder->addPlugin($plugin, 1);
        $clientBuilder->addPlugin($plugin, 2);

        $this->assertCount(2, $clientBuilder->getPlugins());
    }

    public function testAddPluginWithSamePriority()
    {
        $plugin = $this->createMock(Plugin::class);
        $clientBuilder = new ClientBuilder();

        $this->expectException(PluginException::class);
        $this->expectExceptionMessage('A plugin with priority 1 already exists.');

        $clientBuilder->addPlugin($plugin, 1);
        $clientBuilder->addPlugin($plugin, 1);
    }

    public function testPluginPriorityOrder()
    {
        $plugin = $this->createMock(Plugin::class);
        $clientBuilder = new ClientBuilder();

        $clientBuilder->addPlugin($plugin, 1);
        $clientBuilder->addPlugin($plugin, 3);
        $clientBuilder->addPlugin($plugin, 2);

        // calling this method triggers plugin sorting
        $clientBuilder->getClient();

        // plugins array keys are used as priority [priority => plugin]
        // so check if order of keys (priority) is sorted
        $this->assertSame(
            [
                0 => 3,
                1 => 2,
                2 => 1
            ],
            array_keys($clientBuilder->getPlugins())
        );
    }
}