<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheTest extends AbstractTestCase
{
    public function testSdkUserCanConfigureCache(): void
    {
        $client = $this->mockClient(new Response(
            headers: ['Cache-Control' => 'max-age=60'],
            body: '{"id":1}'
        ));

        $api = new JsonApi($client);
        $api
            ->setup()->cache(new ArrayAdapter())
            ->defaultTtl(60);

        $first = $api->raw()->fetch();
        $second = $api->raw()->fetch();

        $this->assertSame(['id' => 1], $first->data());
        $this->assertSame(['id' => 1], $second->data());
        $this->assertCount(1, $client->getRequests());
    }

    public function testEndpointCanOverrideCacheConfiguration(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));
        $api = new FakeApi($client);
        $api->setup()->cache(new ArrayAdapter())->methods(['GET']);

        $first = $api->users()->createWithEndpointCache(['name' => 'John']);
        $second = $api->users()->createWithEndpointCache(['name' => 'John']);

        $this->assertSame(['id' => 1, 'name' => 'John'], $first->data());
        $this->assertSame(['id' => 1, 'name' => 'John'], $second->data());
        $this->assertCount(1, $client->getRequests());
    }

    public function testResourceCacheOverrideWinsOverEndpointCacheDefault(): void
    {
        $client = $this->mockClient(
            new Response(body: '{"id":1,"name":"John"}'),
            new Response(body: '{"id":2,"name":"Jane"}')
        );
        $api = new FakeApi($client);
        $api->setup()->cache(new ArrayAdapter())->methods(['GET']);

        $first = $api
            ->users()
            ->withCache(fn($cache) => $cache->methods(['GET']))
            ->createWithEndpointCache(['name' => 'John']);

        $second = $api
            ->users()
            ->withCache(fn($cache) => $cache->methods(['GET']))
            ->createWithEndpointCache(['name' => 'John']);

        $this->assertSame(['id' => 1, 'name' => 'John'], $first->data());
        $this->assertSame(['id' => 2, 'name' => 'Jane'], $second->data());
        $this->assertCount(2, $client->getRequests());
    }

    public function testEndpointCacheOverridesAreAppliedInFluentOrder(): void
    {
        $client = $this->mockClient(
            new Response(body: '{"id":1,"name":"John"}'),
            new Response(body: '{"id":2,"name":"Jane"}')
        );
        $api = new FakeApi($client);
        $api->setup()->cache(new ArrayAdapter())->methods(['GET']);

        $first = $api->users()->createWithChainedEndpointCache(['name' => 'John']);
        $second = $api->users()->createWithChainedEndpointCache(['name' => 'John']);

        $this->assertSame(['id' => 1, 'name' => 'John'], $first->data());
        $this->assertSame(['id' => 2, 'name' => 'Jane'], $second->data());
        $this->assertCount(2, $client->getRequests());
    }

    public function testResourceCacheOverrideRequiresGlobalCacheConfiguration(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Endpoint cache overrides require API-level cache configuration.');

        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));
        $api = new FakeApi($client);

        $api
            ->users()
            ->withCache(fn($cache) => $cache->defaultTtl(60))
            ->find(1);
    }

    public function testCachedResponsesUseCurrentResourceConfig(): void
    {
        $client = $this->mockClient(new Response(
            headers: ['Cache-Control' => 'max-age=60'],
            body: '{"id":1,"name":"John"}'
        ));
        $api = new FakeApi($client);
        $api->setup()->cache(new ArrayAdapter())->defaultTtl(60);

        $utc = $api
            ->users()
            ->withConfig(['timezone' => 'UTC'])
            ->find(1);

        $lisbon = $api
            ->users()
            ->withConfig(['timezone' => 'Europe/Lisbon'])
            ->find(1);

        $this->assertSame('UTC', $utc->getTimezone());
        $this->assertSame('Europe/Lisbon', $lisbon->getTimezone());
        $this->assertCount(1, $client->getRequests());
    }

    public function testResourceConfigAndCacheOverridesComposeInEitherOrder(): void
    {
        $client = $this->mockClient(
            new Response(body: '{"id":1,"name":"John"}'),
            new Response(body: '{"id":2,"name":"Jane"}')
        );
        $api = new FakeApi($client);
        $api->setup()->cache(new ArrayAdapter())->methods(['GET']);

        $first = $api
            ->users()
            ->withConfig(['timezone' => 'Europe/Lisbon'])
            ->withCache(fn($cache) => $cache->methods([]))
            ->find(1);

        $second = $api
            ->users()
            ->withCache(fn($cache) => $cache->methods([]))
            ->withConfig(['timezone' => 'Europe/Lisbon'])
            ->find(2);

        $this->assertSame('Europe/Lisbon', $first->getTimezone());
        $this->assertSame('Europe/Lisbon', $second->getTimezone());
        $this->assertCount(2, $client->getRequests());
    }
}
