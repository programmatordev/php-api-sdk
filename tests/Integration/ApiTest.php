<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Message\Authentication;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Event\PreRequestEvent;
use ProgrammatorDev\Api\Event\ResponseContentsEvent;
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

    public function testRequest()
    {
        $this->mockClient->addResponse(new Response(body: MockResponse::SUCCESS));

        $response = $this->api->request(
            method: 'GET',
            path: '/path'
        );

        $this->assertSame(MockResponse::SUCCESS, $response);
    }

    public function testBaseUrl()
    {
        $this->assertNull($this->api->getBaseUrl());

        $this->api->setBaseUrl(self::BASE_URL);

        $this->assertSame(self::BASE_URL, $this->api->getBaseUrl());
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
        $this->assertNull($this->api->getCacheBuilder());

        $cachePool = $this->createMock(CacheItemPoolInterface::class);

        $this->api->setCacheBuilder(new CacheBuilder($cachePool));

        $cachePool->expects($this->once())->method('save');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testLogger()
    {
        $this->assertNull($this->api->getLoggerBuilder());

        $logger = $this->createMock(LoggerInterface::class);

        $this->api->setLoggerBuilder(new LoggerBuilder($logger));

        // count equals 2 because of the request and response log
        $logger->expects($this->exactly(2))->method('info');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testCacheWithLogger()
    {
        $this->assertNull($this->api->getCacheBuilder());
        $this->assertNull($this->api->getLoggerBuilder());

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->api->setCacheBuilder(new CacheBuilder($cachePool));
        $this->api->setLoggerBuilder(new LoggerBuilder($logger));

        // count equals 3 because of the request, response and cache log
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
        $this->assertNull($this->api->getAuthentication());

        $authentication = $this->createConfiguredMock(Authentication::class, [
            'authenticate' => $this->createMock(RequestInterface::class)
        ]);

        $this->api->setAuthentication($authentication);

        $authentication->expects($this->once())->method('authenticate');

        $this->api->request(
            method: 'GET',
            path: '/path'
        );
    }

    public function testPreRequestListener()
    {
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

    #[DataProvider('provideBuildUrlData')]
    public function testBuildUrl(?string $baseUrl, string $path, array $query, string $expectedUrl)
    {
        $this->api->addPreRequestListener(function(PreRequestEvent $event) use ($expectedUrl) {
            $url = (string) $event->getRequest()->getUri();

            $this->assertSame($expectedUrl, $url);
        });

        $this->api->setBaseUrl($baseUrl);
        $this->api->request(method: 'GET', path: $path, query: $query);
    }

    public static function provideBuildUrlData(): \Generator
    {
        yield 'no base url' => [null, '/path', [], '/path'];
        yield 'base url' => [self::BASE_URL, '/path', [], 'https://base.com/url/path'];
        yield 'path full url' => [self::BASE_URL, 'https://fullurl.com/path', [], 'https://fullurl.com/path'];
        yield 'duplicated slashes' => [self::BASE_URL, '////path', [], 'https://base.com/url/path'];
        yield 'query' => [self::BASE_URL, '/path', ['foo' => 'bar'], 'https://base.com/url/path?foo=bar'];
        yield 'path query' => [self::BASE_URL, '/path?test=true', ['foo' => 'bar'], 'https://base.com/url/path?test=true&foo=bar'];
        yield 'query replace' => [self::BASE_URL, '/path?test=true', ['test' => 'false'], 'https://base.com/url/path?test=false'];
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