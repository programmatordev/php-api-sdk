<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Message\Authentication;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Event\PostRequestEvent;
use ProgrammatorDev\Api\Event\ResponseEvent;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use ProgrammatorDev\Api\Test\MockResponse;
use ProgrammatorDev\YetAnotherPhpValidator\Exception\ValidationException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

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

    public function testSetters()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $authentication = $this->createConfiguredMock(Authentication::class, [
            'authenticate' => $this->createMock(RequestInterface::class)
        ]);

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setClientBuilder(new ClientBuilder());
        $this->class->setCacheBuilder(new CacheBuilder($pool));
        $this->class->setLoggerBuilder(new LoggerBuilder($logger));
        $this->class->setAuthentication($authentication);

        $this->assertSame(self::BASE_URL, $this->class->getBaseUrl());
        $this->assertInstanceOf(ClientBuilder::class, $this->class->getClientBuilder());
        $this->assertInstanceOf(CacheBuilder::class, $this->class->getCacheBuilder());
        $this->assertInstanceOf(LoggerBuilder::class, $this->class->getLoggerBuilder());
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

    public function testCache()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setCacheBuilder(new CacheBuilder($pool));

        $pool->expects($this->once())->method('save');

        $this->class->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setLoggerBuilder(new LoggerBuilder($logger));

        // request + response log
        $logger->expects($this->exactly(2))->method('info');

        $this->class->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testCacheWithLogger()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setCacheBuilder(new CacheBuilder($pool));
        $this->class->setLoggerBuilder(new LoggerBuilder($logger));

        // request + response + cache log
        $logger->expects($this->exactly(3))->method('info');

        // error suppression to hide expected warning of null cache item in CacheLoggerListener
        // https://docs.phpunit.de/en/10.5/error-handling.html#ignoring-issue-suppression
        // TODO maybe allow user to add cache listeners to CacheBuilder and create a mock?
        @$this->class->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testAuthentication()
    {
        $authentication = $this->createConfiguredMock(Authentication::class, [
            'authenticate' => $this->createMock(RequestInterface::class)
        ]);

        $this->class->setBaseUrl(self::BASE_URL);
        $this->class->setAuthentication($authentication);

        $authentication->expects($this->once())->method('authenticate');

        $this->class->request(
            method: 'GET',
            path: '/path'
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