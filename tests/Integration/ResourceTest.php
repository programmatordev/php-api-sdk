<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\AbstractTestCase;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Fixture\User;

class ResourceTest extends AbstractTestCase
{
    private Client $client;

    private FakeApi $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client();
        $this->api = new FakeApi($this->client);
    }

    public function testResourceGetMapsEntity(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $user = $this->api->users()->find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John', $user->getName());
        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $this->client->getLastRequest()->getUri());
    }

    public function testResourcePathParametersAreEncoded(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->find('john/doe');

        $this->assertSame('https://api.example.com/users/john%2Fdoe?locale=en', (string) $this->client->getLastRequest()->getUri());
    }

    public function testResourceOptionsAreImmutable(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $users = $this->api->users();

        $users->query('active', true)->find(1);
        $users->find(2);

        $requests = $this->client->getRequests();

        $this->assertSame('https://api.example.com/users/1?locale=en&active=1', (string) $requests[0]->getUri());
        $this->assertSame('https://api.example.com/users/2?locale=en', (string) $requests[1]->getUri());
    }

    public function testEndpointQueryOverridesResourceAndGlobalDefaults(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api
            ->users()
            ->query('locale', 'fr')
            ->findWithEndpointLocale(1, 'pt');

        $this->assertSame('https://api.example.com/users/1?locale=pt', (string) $this->client->getLastRequest()->getUri());
    }

    public function testNullQueryValuesAreOmitted(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api
            ->users()
            ->query('empty', null)
            ->find(1);

        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $this->client->getLastRequest()->getUri());
    }

    public function testEntityCanBeMappedFromResponseKey(): void
    {
        $this->client->addResponse(new Response(body: '{"data":{"id":1,"name":"John"}}'));

        $user = $this->api->users()->findFromEnvelope(1);

        $this->assertSame(1, $user->getId());
        $this->assertSame('John', $user->getName());
    }
}
