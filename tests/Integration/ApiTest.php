<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Client\Common\Plugin;
use Http\Mock\Client;
use Http\Promise\Promise;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;
use ProgrammatorDev\Api\Http\Method;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
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
        $client = new Client();
        $client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $response = (new FakeApi($client))->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame(['id' => 1, 'name' => 'John'], $response->data());
        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $client->getLastRequest()->getUri());
    }

    public function testApiCanSendRequestWithDefaultQuery(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))
            ->withDefaultQuery('units', 'metric')
            ->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame('https://api.example.com/users/1?locale=en&units=metric', (string) $client->getLastRequest()->getUri());
    }

    public function testApiCanSendRequestWithDefaultHeader(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        (new FakeApi($client))
            ->withDefaultHeader('Accept', 'application/json')
            ->send(Method::GET, '/users/{id}', ['id' => 1]);

        $this->assertSame('application/json', $client->getLastRequest()->getHeaderLine('Accept'));
    }

    public function testApiSetupCanConfigureRequestBehavior(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

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
        $client = new Client();
        $client->addResponse(new Response(body: '{"ok":false}'));

        $api = new class extends Api {};
        $setup = $api->setup();

        $setup->client($client);
        $setup->baseUrl('https://api.example.com');

        $setup->auth()->header('X-Auth', 'secret');
        $setup->plugins()->add($this->headerPlugin('X-Plugin', 'plugin'));
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
        $client = new Client();
        $client->addResponse(new Response(status: 404, body: '{"message":"Missing"}'));

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
        $client = new Client();
        $client->addResponse(new Response(
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

    private function headerPlugin(string $name, string $value): Plugin
    {
        return new class($name, $value) implements Plugin {
            public function __construct(
                private readonly string $name,
                private readonly string $value
            ) {}

            public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
            {
                return $next($request->withHeader($this->name, $this->value));
            }
        };
    }
}
