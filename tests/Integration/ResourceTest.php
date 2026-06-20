<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Fixture\User;
use ProgrammatorDev\Api\Test\Fixture\UserEnvelope;

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
        $this->assertSame('UTC', $user->getTimezone());
        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $this->client->getLastRequest()->getUri());
    }

    /**
     * @dataProvider resourceVerbProvider
     */
    public function testResourceCanSendHttpVerbs(string $verb): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->sendWithVerb($verb);

        $this->assertSame($verb, $this->client->getLastRequest()->getMethod());
    }

    public function testResourceCanSendJsonBody(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->createWithJson(['name' => 'John']);

        $request = $this->client->getLastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('{"name":"John"}', (string) $request->getBody());
    }

    public function testResourceCanSendFormBody(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->createWithForm(['name' => 'John Doe']);

        $request = $this->client->getLastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame('name=John+Doe', (string) $request->getBody());
    }

    public function testResourceCanSendRawStringBody(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->createWithBody('raw-body');

        $request = $this->client->getLastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('raw-body', (string) $request->getBody());
    }

    public function testResourceCanSendStreamBody(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $stream = Stream::create('stream-body');

        $this->api->users()->createWithBody($stream);

        $request = $this->client->getLastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('stream-body', (string) $request->getBody());
    }

    public function testEndpointCanSetRequestQueryAndHeaders(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->findWithEndpointOptions(1);

        $request = $this->client->getLastRequest();

        $this->assertSame('https://api.example.com/users/1?locale=en&active=1', (string) $request->getUri());
        $this->assertSame('acme', $request->getHeaderLine('X-Tenant'));
    }

    public function testResourceBodyRejectsArrayData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Use json() or form() to send array request data.');

        $this->api->users()->createWithInvalidBody(['name' => 'John']);
    }

    public function testResourcePathParametersAreEncoded(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api->users()->find('john/doe');

        $this->assertSame('https://api.example.com/users/john%2Fdoe?locale=en', (string) $this->client->getLastRequest()->getUri());
    }

    public function testEndpointOptionsDoNotLeakBetweenResourceCalls(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));
        $this->client->addResponse(new Response(body: '{"id":2,"name":"Jane"}'));

        $users = $this->api->users();

        $users->findWithActive(1);
        $users->find(2);

        $requests = $this->client->getRequests();

        $this->assertSame('https://api.example.com/users/1?locale=en&active=1', (string) $requests[0]->getUri());
        $this->assertSame('https://api.example.com/users/2?locale=en', (string) $requests[1]->getUri());
    }

    public function testSdkSpecificResourceChainCanSetQueryOptions(): void
    {
        $this->client->addResponse(new Response(body: '{"data":[{"id":1,"name":"John"}]}'));

        $this->api
            ->users()
            ->withStatus('active')
            ->all();

        $this->assertSame('https://api.example.com/users?locale=en&status=active', (string) $this->client->getLastRequest()->getUri());
    }

    public function testSdkSpecificResourceChainDoesNotLeakBetweenResourceCalls(): void
    {
        $this->client->addResponse(new Response(body: '{"data":[{"id":1,"name":"John"}]}'));
        $this->client->addResponse(new Response(body: '{"data":[{"id":2,"name":"Jane"}]}'));

        $users = $this->api->users();

        $users->withStatus('active')->all();
        $users->all();

        $requests = $this->client->getRequests();

        $this->assertSame('https://api.example.com/users?locale=en&status=active', (string) $requests[0]->getUri());
        $this->assertSame('https://api.example.com/users?locale=en', (string) $requests[1]->getUri());
    }

    public function testEndpointQueryOverridesGlobalDefaults(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api
            ->users()
            ->findWithEndpointLocale(1, 'pt');

        $this->assertSame('https://api.example.com/users/1?locale=pt', (string) $this->client->getLastRequest()->getUri());
    }

    public function testResourceCanReadSdkConfig(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api
            ->users()
            ->findWithConfiguredTimezone(1);

        $this->assertSame('https://api.example.com/users/1?locale=en&timezone=UTC', (string) $this->client->getLastRequest()->getUri());
    }

    public function testResourceUsesLatestApiSetupWhenRequestIsSent(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $users = $this->api->users();

        $this->api->setup()->defaultQuery('units', 'metric');

        $users->find(1);

        $this->assertSame('https://api.example.com/users/1?locale=en&units=metric', (string) $this->client->getLastRequest()->getUri());
    }

    public function testNullQueryValuesAreOmitted(): void
    {
        $this->client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $this->api
            ->users()
            ->findWithEmptyQuery(1);

        $this->assertSame('https://api.example.com/users/1?locale=en', (string) $this->client->getLastRequest()->getUri());
    }

    public function testEntityCanBeMappedFromResponseKey(): void
    {
        $this->client->addResponse(new Response(body: '{"data":{"id":1,"name":"John"}}'));

        $user = $this->api->users()->findFromEnvelope(1);

        $this->assertSame(1, $user->getId());
        $this->assertSame('John', $user->getName());
    }

    public function testCollectionCanBeMappedFromResponseKey(): void
    {
        $this->client->addResponse(new Response(body: '{"data":[{"id":1,"name":"John"},{"id":2,"name":"Jane"}]}'));

        $users = $this->api->users()->all();

        $this->assertContainsOnlyInstancesOf(User::class, $users);
        $this->assertSame(1, $users[0]->getId());
        $this->assertSame('John', $users[0]->getName());
        $this->assertSame(2, $users[1]->getId());
        $this->assertSame('Jane', $users[1]->getName());
    }

    public function testResponseCanBeMappedToEnvelope(): void
    {
        $this->client->addResponse(new Response(status: 202, body: '{"data":{"id":1,"name":"John"}}'));

        $envelope = $this->api->users()->findEnvelope(1);

        $this->assertInstanceOf(UserEnvelope::class, $envelope);
        $this->assertSame(202, $envelope->getStatusCode());
        $this->assertSame('UTC', $envelope->getTimezone());
        $this->assertSame(1, $envelope->getUser()->getId());
        $this->assertSame('John', $envelope->getUser()->getName());
        $this->assertSame('UTC', $envelope->getUser()->getTimezone());
    }

    public static function resourceVerbProvider(): array
    {
        return [
            'get' => ['GET'],
            'post' => ['POST'],
            'put' => ['PUT'],
            'patch' => ['PATCH'],
            'delete' => ['DELETE'],
            'head' => ['HEAD'],
            'options' => ['OPTIONS'],
            'connect' => ['CONNECT'],
            'trace' => ['TRACE'],
        ];
    }
}
