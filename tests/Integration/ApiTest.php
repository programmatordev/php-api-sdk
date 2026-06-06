<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Method;
use ProgrammatorDev\Api\Test\Fixture\FakeApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ApiTest extends AbstractTestCase
{
    public function testConfigCanBeSetAndReadBySdkApi(): void
    {
        $api = new class extends Api {};

        $api
            ->config(['timezone' => 'UTC'])
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
}
