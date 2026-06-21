<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ClientBuilderTest extends AbstractTestCase
{
    public function testClientBuilderUsesDiscoveredDefaults(): void
    {
        $clientBuilder = new ClientBuilder();

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertInstanceOf(RequestFactoryInterface::class, $clientBuilder->getRequestFactory());
        $this->assertInstanceOf(StreamFactoryInterface::class, $clientBuilder->getStreamFactory());
    }

    public function testClientBuilderAcceptsConstructorValues(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $clientBuilder = new ClientBuilder($client, $requestFactory, $streamFactory);

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertSame($requestFactory, $clientBuilder->getRequestFactory());
        $this->assertSame($streamFactory, $clientBuilder->getStreamFactory());
    }

    public function testClientBuilderCanBeConfiguredFluently(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $clientBuilder = (new ClientBuilder())
            ->client($client)
            ->requestFactory($requestFactory)
            ->streamFactory($streamFactory);

        $this->assertInstanceOf(ClientInterface::class, $clientBuilder->getClient());
        $this->assertSame($requestFactory, $clientBuilder->getRequestFactory());
        $this->assertSame($streamFactory, $clientBuilder->getStreamFactory());
    }
}
