<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\ResponseEvent;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use ProgrammatorDev\Api\Test\MockResponse;
use ProgrammatorDev\YetAnotherPhpValidator\Exception\ValidationException;
use Psr\Http\Message\StreamInterface;

class ApiTest extends AbstractTestCase
{
    private const BASE_URL = 'https://base.com/url';

    private $class;

    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // set protected functions to public for testing
        $this->class = new class extends Api {
            public function request(
                string $method,
                string $path,
                array $query = [],
                array $headers = [],
                StreamInterface|string $body = null
            ): mixed
            {
                return parent::request($method, $path, $query, $headers, $body);
            }

            public function getBaseUrl(): ?string
            {
                return parent::getBaseUrl();
            }

            public function setBaseUrl(string $baseUrl): Api
            {
                return parent::setBaseUrl($baseUrl);
            }

            public function getAuthentication(): ?Authentication
            {
                return parent::getAuthentication();
            }

            public function setAuthentication(?Authentication $authentication): Api
            {
                return parent::setAuthentication($authentication);
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
        $this->class->setAuthentication(new Authentication\Bearer('token'));

        $this->assertSame(self::BASE_URL, $this->class->getBaseUrl());
        $this->assertInstanceOf(ClientBuilder::class, $this->class->getClientBuilder());
        $this->assertInstanceOf(Authentication::class, $this->class->getAuthentication());
    }

    public function testRequest()
    {
        $this->mockClient->addResponse(new Response(body: MockResponse::SUCCESS));

        $this->class->setBaseUrl(self::BASE_URL);

        $response = $this->class->request(
            method: 'GET',
            path: '/path'
        );

        $this->assertSame(MockResponse::SUCCESS, $response);
    }

    public function testMissingBaseUrl()
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('A base URL must be set.');

        $this->class->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testInvalidBaseUrl()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The value is not a valid URL address, "invalid" given.');

        $this->class->setBaseUrl('invalid');
    }

    public function testAuthentication()
    {
        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setAuthentication(new Authentication\Bearer('token'));

        $this->class->request(
            method: 'GET',
            path: '/path'
        );

        $this->assertArrayHasKey(
            AuthenticationPlugin::class,
            $this->class->getClientBuilder()->getPlugins()
        );
    }

    public function testPostRequestHandler()
    {
        $this->mockClient->addResponse(new Response(status: 500));

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->addPostRequestHandler(function(PostRequestEvent $event) {
            $statusCode = $event->getResponse()->getStatusCode();

            if ($statusCode === 500) {
                throw new \Exception('TestMessage');
            }
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TestMessage');

        $this->class->request(
            method: 'GET',
            path: '/path'
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
            path: '/path'
        );

        $this->assertIsArray($response);
    }
}