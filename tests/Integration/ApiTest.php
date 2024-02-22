<?php

namespace Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use ProgrammatorDev\Api\Test\MockResponse;
use Psr\Http\Message\ResponseInterface;

class ApiTest extends AbstractTestCase
{
    private const BASE_URL = 'https://pokeapi.co/api/v2';

    private $class;

    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // set protected functions to public for testing
        $this->class = new class extends Api {
            public function getBaseUrl(): ?string
            {
                return parent::getBaseUrl();
            }

            public function setBaseUrl(string $baseUrl): Api
            {
                return parent::setBaseUrl($baseUrl);
            }
        };

        // set mock client
        $this->mockClient = new Client();
        $this->class->setClientBuilder(new ClientBuilder($this->mockClient));
    }

    public function testGettersAndSetters()
    {
        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setClientBuilder(new ClientBuilder());

        $this->assertSame(self::BASE_URL, $this->class->getBaseUrl());
        $this->assertInstanceOf(ClientBuilder::class, $this->class->getClientBuilder());
    }

    public function testRequest()
    {
        $this->mockClient->addResponse(new Response(body: MockResponse::SUCCESS));

        $this->class->setBaseUrl(self::BASE_URL);

        $response = $this->class->request(
            method: 'GET',
            path: '/pokemon'
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testMissingBaseUrl()
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('A base URL must be set.');

        $this->class->request(
            method: 'GET',
            path: '/pokemon'
        );
    }
}