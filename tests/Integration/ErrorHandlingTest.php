<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\InvalidApiKeyException;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Fixture\NotFoundException;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ErrorHandlingTest extends AbstractTestCase
{
    public function testHttpErrorStatusDoesNotThrowByDefault(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 404, body: '{"message":"Missing user"}'));

        $response = (new JsonApi($client))->raw()->fetch();

        $this->assertSame(404, $response->raw()->getStatusCode());
        $this->assertSame(['message' => 'Missing user'], $response->data());
    }

    public function testConfiguredStatusErrorThrows(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 404, body: '{"message":"Missing user"}'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Missing user');

        (new JsonApi($client))
            ->throwNotFoundErrors()
            ->raw()
            ->fetch();
    }

    public function testConfiguredStatusErrorCanMapDirectlyToThrowableClass(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 404, body: '{"message":"Missing user"}'));

        $this->expectException(NotFoundException::class);

        (new JsonApi($client))
            ->throwSimpleNotFoundErrors()
            ->raw()
            ->fetch();
    }

    public function testConfiguredStatusErrorCanMapMultipleStatuses(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 401, body: '{"message":"Invalid API key"}'));

        $this->expectException(InvalidApiKeyException::class);

        (new JsonApi($client))
            ->throwStatusErrors()
            ->raw()
            ->fetch();
    }

    public function testConfiguredCustomErrorHandlerThrowsWhenMatched(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 401, body: '{"code":"invalid_api_key","message":"Invalid API key"}'));

        $this->expectException(InvalidApiKeyException::class);
        $this->expectExceptionMessage('Invalid API key');

        (new JsonApi($client))
            ->throwInvalidApiKeyErrors()
            ->raw()
            ->fetch();
    }

    public function testConfiguredCustomErrorHandlerDoesNotThrowWhenUnmatched(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 401, body: '{"code":"rate_limited","message":"Too many requests"}'));

        $response = (new JsonApi($client))
            ->throwInvalidApiKeyErrors()
            ->raw()
            ->fetch();

        $this->assertSame(401, $response->raw()->getStatusCode());
        $this->assertSame(['code' => 'rate_limited', 'message' => 'Too many requests'], $response->data());
    }
}
