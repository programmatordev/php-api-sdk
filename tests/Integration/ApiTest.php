<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\ResponseEvent;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use ProgrammatorDev\Api\Test\MockResponse;

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

            public function addPostRequestHandler(callable $handler, int $priority = 0): Api
            {
                return parent::addPostRequestHandler($handler, $priority);
            }

            public function addResponseHandler(callable $handler, int $priority = 0): Api
            {
                return parent::addResponseHandler($handler, $priority);
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

        $this->assertSame(MockResponse::SUCCESS, $response);
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

    public function testPostRequestHandler()
    {
        $this->mockClient->addResponse(new Response(status: 500));

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->addPostRequestHandler(function(PostRequestEvent $event) {
            $statusCode = $event->getResponse()->getStatusCode();

            if ($statusCode === 500) {
                throw new \Exception('PostRequestEvent handler exception test.');
            }
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('PostRequestEvent handler exception test.');

        $this->class->request(
            method: 'GET',
            path: '/pokemon'
        );
    }

    public function testResponseHandler()
    {
        $this->mockClient->addResponse(new Response(body: MockResponse::SUCCESS));

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->addResponseHandler(function(ResponseEvent $event) {
            $contents = json_decode($event->getContents(), true);
            $event->setContents($contents);
        });

        $response = $this->class->request(
            method: 'GET',
            path: '/pokemon'
        );

        $this->assertIsArray($response);
    }
}