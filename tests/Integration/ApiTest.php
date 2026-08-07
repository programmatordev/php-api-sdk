<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;
use ProgrammatorDev\Api\Http\Method;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Fixture\HeaderPlugin;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ApiTest extends AbstractTestCase
{
    public function testConfigCanBeSetAndReadBySdkApi(): void
    {
        $api = new class extends Api {};

        $api
            ->config(['timezone' => 'UTC'], defaults: ['timezone' => 'Europe/Lisbon'])
            ->merge(['units' => 'metric']);

        $this->assertSame('UTC', $api->config()->get('timezone'));
        $this->assertSame('metric', $api->config()->get('units'));
        $this->assertSame('en', $api->config()->get('locale', 'en'));
        $this->assertSame([
            'timezone' => 'UTC',
            'units' => 'metric',
        ], $api->config()->all());
    }

    public function testApiCanSendPublicRequest(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        $response = (new FakeApi($client))->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame(['id' => 1, 'name' => 'John'], $response->data());
        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $client->getLastRequest()->getUri());
    }

    public function testApiCanSendPublicRequestWithQueryHeadersAndBody(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))->send(
            method: Method::POST,
            path: '/users',
            query: ['active' => true],
            headers: ['Content-Type' => 'application/json'],
            body: '{"name":"John"}'
        );

        $request = $client->getLastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://api.example.com/users?locale=en&active=1', (string) $request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('{"name":"John"}', (string) $request->getBody());
    }

    public function testApiCanSendPublicRequestWithBackedEnums(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))->send(
            method: Method::GET,
            path: '/users',
            query: ['status' => ApiRequestValue::ACTIVE],
            headers: ['X-Status' => ApiRequestValue::ACTIVE]
        );

        $request = $client->getLastRequest();

        $this->assertSame('active', $this->queryFromLastRequest($client)['status']);
        $this->assertSame('active', $request->getHeaderLine('X-Status'));
    }

    public function testApiCanSendRequestWithDefaultQuery(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))
            ->withDefaultQuery('units', 'metric')
            ->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame('https://api.example.com/users/1?locale=en&units=metric', (string) $client->getLastRequest()->getUri());
    }

    public function testRequestQueryTakesPrecedenceOverUrlQueryAndDefaults(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))
            ->withDefaultQuery('locale', 'en')
            ->send(Method::GET, '/users?locale=pt&page=2', query: [
                'page' => 1,
                'units' => 'metric',
            ]);

        $this->assertSame('https://api.example.com/users?locale=en&page=1&units=metric', (string) $client->getLastRequest()->getUri());
    }

    public function testApiCanUseConfigValuesAsDefaultQueries(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        $api = new class extends Api {};
        $setup = $api->setup();

        $setup->client($client);
        $setup
            ->baseUrl('https://api.example.com')
            ->defaultQueries($api->config([
                'locale' => 'pt',
                'version' => 'v2',
                'internal' => true,
            ])->only('locale', 'version'));
        $setup->responses()->json();

        $api->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame('https://api.example.com/users/1?locale=pt&version=v2', (string) $client->getLastRequest()->getUri());
    }

    public function testApiCanSendRequestWithDefaultHeader(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))
            ->withDefaultHeader('Accept', 'application/json')
            ->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame('application/json', $client->getLastRequest()->getHeaderLine('Accept'));
    }

    public function testApiSetupCanConfigureRequestBehavior(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        $api = new class extends Api {};
        $setup = $api->setup();

        $setup->client($client);
        $setup
            ->baseUrl('https://api.example.com')
            ->defaultQuery('locale', 'en')
            ->defaultHeader('Accept', 'application/json');

        $setup->responses()->json();

        $response = $api->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame(['id' => 1, 'name' => 'John'], $response->data());
        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $client->getLastRequest()->getUri());
        $this->assertSame('application/json', $client->getLastRequest()->getHeaderLine('Accept'));
    }

    public function testApiSendUsesConfiguredPipeline(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":false}'));

        $api = new class extends Api {};
        $setup = $api->setup();

        $setup->client($client);
        $setup->baseUrl('https://api.example.com');

        $setup->auth()->header('X-Auth', 'secret');
        $setup->plugins()->add(new HeaderPlugin('X-Plugin', 'plugin'));
        $setup->hooks()->beforeRequest(
            fn (RequestContext $context): RequestInterface => $context->request()->withHeader('X-Before-Hook', 'before')
        );
        $setup->hooks()->afterResponse(
            fn (ResponseContext $context): Response => new Response(body: '{"ok":true}')
        );
        $setup->responses()->json();

        $response = $api->send(Method::GET, '/status');

        $this->assertSame(['ok' => true], $response->data());
        $this->assertSame('secret', $client->getLastRequest()->getHeaderLine('X-Auth'));
        $this->assertSame('plugin', $client->getLastRequest()->getHeaderLine('X-Plugin'));
        $this->assertSame('before', $client->getLastRequest()->getHeaderLine('X-Before-Hook'));
    }

    public function testApiSendUsesConfiguredErrors(): void
    {
        $client = $this->mockClient(new Response(status: 404, body: '{"message":"Missing"}'));

        $api = new class extends Api {};
        $setup = $api->setup();

        $setup->client($client);
        $setup->baseUrl('https://api.example.com');

        $setup->responses()->json();
        $setup->errors()->status(404, fn (): \Throwable => new \RuntimeException('Missing'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing');

        $api->send(Method::GET, '/missing');
    }

    public function testApiSendUsesConfiguredCache(): void
    {
        $client = $this->mockClient(new Response(
            headers: ['Cache-Control' => 'max-age=60'],
            body: '{"id":1}'
        ));

        $api = new class extends Api {};
        $setup = $api->setup();

        $setup->client($client);
        $setup->baseUrl('https://api.example.com');

        $setup->cache(new ArrayAdapter())->defaultTtl(60);
        $setup->responses()->json();

        $first = $api->send(Method::GET, '/users/{id}', ['id' => 1]);
        $second = $api->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame(['id' => 1], $first->data());
        $this->assertSame(['id' => 1], $second->data());
        $this->assertCount(1, $client->getRequests());
    }
}

enum ApiRequestValue: string
{
    case ACTIVE = 'active';
}
