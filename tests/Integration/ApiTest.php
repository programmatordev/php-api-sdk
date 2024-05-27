<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Message\Authentication;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Event\ResponseContentsEvent;
use ProgrammatorDev\Api\Exception\ConfigException;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use ProgrammatorDev\Api\Test\MockResponse;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class ApiTest extends AbstractTestCase
{
    private const BASE_URL = 'https://base.com/url';

    private Api $api;

    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // create anonymous class
        $this->api = new class extends Api {};

        // set mock client
        $this->mockClient = new Client();
        $this->api->setClientBuilder(new ClientBuilder($this->mockClient));
    }

    public function testSetters()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $authentication = $this->createConfiguredMock(Authentication::class, [
            'authenticate' => $this->createMock(RequestInterface::class)
        ]);

        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->setClientBuilder(new ClientBuilder());
        $this->api->setCacheBuilder(new CacheBuilder($pool));
        $this->api->setLoggerBuilder(new LoggerBuilder($logger));
        $this->api->setAuthentication($authentication);

        $this->assertSame(self::BASE_URL, $this->api->getBaseUrl());
        $this->assertInstanceOf(ClientBuilder::class, $this->api->getClientBuilder());
        $this->assertInstanceOf(CacheBuilder::class, $this->api->getCacheBuilder());
        $this->assertInstanceOf(LoggerBuilder::class, $this->api->getLoggerBuilder());
        $this->assertInstanceOf(Authentication::class, $this->api->getAuthentication());
    }

    public function testRequest()
    {
        $this->mockClient->addResponse(new Response(body: MockResponse::SUCCESS));

        $this->api->setBaseUrl(self::BASE_URL);

        $response = $this->api->request(
            method: 'GET',
            path: '/path'
        );

        $this->assertSame(MockResponse::SUCCESS, $response);
    }

    public function testMissingBaseUrl()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('A base URL must be set.');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testQueryDefaults()
    {
        $this->api->addQueryDefault('test', true);
        $this->assertTrue($this->api->getQueryDefault('test'));

        $this->api->removeQueryDefault('test');
        $this->assertNull($this->api->getQueryDefault('test'));
    }

    public function testHeaderDefaults()
    {
        $this->api->addHeaderDefault('X-Test', true);
        $this->assertTrue($this->api->getHeaderDefault('X-Test'));

        $this->api->removeHeaderDefault('X-Test');
        $this->assertNull($this->api->getHeaderDefault('X-Test'));
    }

    public function testCache()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);

        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->setCacheBuilder(new CacheBuilder($pool));

        $pool->expects($this->once())->method('save');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->setLoggerBuilder(new LoggerBuilder($logger));

        // request + response log
        $logger->expects($this->exactly(2))->method('info');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testCacheWithLogger()
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->setCacheBuilder(new CacheBuilder($pool));
        $this->api->setLoggerBuilder(new LoggerBuilder($logger));

        // request + response + cache log
        $logger->expects($this->exactly(3))->method('info');

        // error suppression to hide expected warning of null cache item in CacheLoggerListener
        // https://docs.phpunit.de/en/10.5/error-handling.html#ignoring-issue-suppression
        // TODO maybe allow user to add cache listeners to CacheBuilder and create a mock?
        @$this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testAuthentication()
    {
        $authentication = $this->createConfiguredMock(Authentication::class, [
            'authenticate' => $this->createMock(RequestInterface::class)
        ]);

        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->setAuthentication($authentication);

        $authentication->expects($this->once())->method('authenticate');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testPreRequestListener()
    {
        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->addPreRequestListener(fn() => throw new \Exception('TestMessage'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TestMessage');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testPostRequestListener()
    {
        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->addPostRequestListener(fn() => throw new \Exception('TestMessage'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TestMessage');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testResponseContentsListener()
    {
        $this->mockClient->addResponse(new Response(body: MockResponse::SUCCESS));

        $this->api->setBaseUrl(self::BASE_URL);
        $this->api->addResponseContentsListener(function(ResponseContentsEvent $event) {
            $contents = json_decode($event->getContents(), true);
            $event->setContents($contents);
        });

        $response = $this->api->request(
            method: 'GET',
            path: '/path'
        );

        $this->assertIsArray($response);
    }

    public function testBuildPath()
    {
        $path = $this->api->buildPath('/path/{parameter1}/multiple/{parameter2}', [
            'parameter1' => 'with',
            'parameter2' => 'parameters'
        ]);

        $this->assertSame('/path/with/multiple/parameters', $path);
    }
}