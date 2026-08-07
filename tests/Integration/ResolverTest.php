<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Fixture\User;
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

    public function testResolverPreservesAbsoluteLinkedUrl(): void
    {
        $this->client->addResponse(new Response(
            body: '{"id":1,"name":"John","friend":{"url":"https://relationships.example.com/users/2"}}'
        ));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $friend = $this->api->users()->findLinked(1)->friend();

        $this->assertSame('Jane', $friend->getName());
        $this->assertSame(
            'https://relationships.example.com/users/2?locale=en',
            (string) $this->client->getLastRequest()->getUri()
        );
    }

    public function testResolverMapsLinkedCollectionOnDemand(): void
    {
        $this->client->addResponse(new Response(
            body: '{"id":1,"name":"John","friend":{"url":"/users/2"},"friends":{"url":"/users/related"}}'
        ));
        $this->client->addResponse(new Response(
            body: '{"data":[{"id":2,"name":"Jane"},{"id":3,"name":"Jack"}]}'
        ));

        $user = $this->api->users()->findLinked(1);

        $this->assertCount(1, $this->client->getRequests());

        $friends = $user->friends();

        $this->assertContainsOnlyInstancesOf(User::class, $friends);
        $this->assertSame(['Jane', 'Jack'], array_map(
            static fn(User $friend): string => $friend->getName(),
            $friends
        ));
        $this->assertSame(
            'https://api.example.com/users/related?locale=en',
            (string) $this->client->getLastRequest()->getUri()
        );
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

    public function testResolverMemoizesEquivalentRelativeAndAbsoluteUrls(): void
    {
        $this->client->addResponse(new Response(body: <<<'JSON'
            {
                "id": 1,
                "name": "John",
                "friend": {"url": "/users/2"},
                "manager": {"url": "https://api.example.com/users/2"}
            }
            JSON));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $user = $this->api->users()->findLinked(1);

        $this->assertSame('Jane', $user->friend()->getName());
        $this->assertSame('Jane', $user->manager()?->getName());
        $this->assertCount(2, $this->client->getRequests());
    }

    public function testResolverIncludesCurrentDefaultQueriesInMemoizationKey(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John","friend":{"url":"/users/2"}}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Janet"}'));

        $user = $this->api->users()->findLinked(1);

        $this->assertSame('Jane', $user->friend()->getName());

        $this->api->withDefaultQuery('locale', 'pt');

        $this->assertSame('Janet', $user->friend()->getName());
        $this->assertSame(
            'https://api.example.com/users/2?locale=pt',
            (string) $this->client->getLastRequest()->getUri()
        );
        $this->assertCount(3, $this->client->getRequests());
    }

    public function testResolverMemoizationDoesNotLeakAcrossResponseGraphs(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John","friend":{"url":"/users/2"}}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John","friend":{"url":"/users/2"}}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Janet"}'));

        $first = $this->api->users()->findLinked(1)->friend();
        $second = $this->api->users()->findLinked(1)->friend();

        $this->assertSame('Jane', $first->getName());
        $this->assertSame('Janet', $second->getName());
        $this->assertCount(4, $this->client->getRequests());
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

    public function testResolverPreservesRepeatedUrlQueryValues(): void
    {
        $this->client->addResponse(new Response(body: '{"data":[],"next":"/users?tag=a&tag=b"}'));
        $this->client->addResponse(new Response(body: '{"data":[],"next":null}'));
        $this->api->withDefaultQuery('tag', 'default');

        $this->api->users()->page()->next();

        $this->assertSame(
            'https://api.example.com/users?tag=a&tag=b&locale=en',
            (string) $this->client->getLastRequest()->getUri()
        );
    }

    public function testResolverPreservesDottedUrlQueryKeys(): void
    {
        $this->client->addResponse(new Response(body: '{"data":[],"next":"/users?filter.name=active"}'));
        $this->client->addResponse(new Response(body: '{"data":[],"next":null}'));
        $this->api->withDefaultQuery('filter.name', 'default');

        $this->api->users()->page()->next();

        $this->assertSame(
            'https://api.example.com/users?filter.name=active&locale=en',
            (string) $this->client->getLastRequest()->getUri()
        );
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
