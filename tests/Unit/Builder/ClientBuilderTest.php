<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\ContentTypePlugin;
use ProgrammatorDev\Api\Builder\ClientBuilder;
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
        $clientBuilder = new ClientBuilder();

        $clientBuilder->addPlugin(new ContentTypePlugin());
        $clientBuilder->addPlugin(new ContentLengthPlugin());

        $this->assertCount(2, $clientBuilder->getPlugins());
    }
}