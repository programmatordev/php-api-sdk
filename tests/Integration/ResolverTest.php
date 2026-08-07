<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ResolverTest extends AbstractTestCase
{
    private Client $client;

    private FakeApi $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client();
        $this->api = new FakeApi($this->client);
    }

    public function testEntityCanResolveLinkedResourceOnDemand(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John","friend":{"url":"/users/2"}}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $user = $this->api->users()->findLinked(1);

        $this->assertSame('John', $user->getName());
        $this->assertCount(1, $this->client->getRequests());

        $friend = $user->friend();

        $this->assertSame('Jane', $friend->getName());
        $this->assertSame('https://api.example.com/users/2?locale=en', (string) $this->client->getLastRequest()->getUri());
        $this->assertCount(2, $this->client->getRequests());
    }

    public function testResolverMemoizesResponsesWithinTheSameContext(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John","friend":{"url":"/users/2"}}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $user = $this->api->users()->findLinked(1);

        $first = $user->friend();
        $second = $user->friend();

        $this->assertNotSame($first, $second);
        $this->assertSame('Jane', $second->getName());
        $this->assertCount(2, $this->client->getRequests());
    }

    public function testEnvelopeCanResolveNextPageOnDemand(): void
    {
        $this->client->addResponse(new Response(body: '{"data":[{"id":1,"name":"John"}],"next":"/users?page=2"}'));
        $this->client->addResponse(new Response(body: '{"data":[{"id":2,"name":"Jane"}],"next":null}'));
        $this->api->withDefaultQuery('page', 1);

        $page = $this->api->users()->page();

        $this->assertSame('John', $page->users()[0]->getName());
        $this->assertCount(1, $this->client->getRequests());

        $next = $page->next();

        $this->assertSame('Jane', $next?->users()[0]->getName());
        $this->assertSame('https://api.example.com/users?page=2&locale=en', (string) $this->client->getLastRequest()->getUri());
        $this->assertCount(2, $this->client->getRequests());
    }

    public function testResolverUsesScopedResourceConfig(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John","friend":{"url":"/users/2"}}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $user = $this->api
            ->users()
            ->withConfig(['timezone' => 'Europe/Lisbon'])
            ->findLinked(1);

        $friend = $user->friend();

        $this->assertSame('Europe/Lisbon', $friend->getTimezone());
    }
}
