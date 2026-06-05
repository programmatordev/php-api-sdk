<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Fixture\PlainApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ResponseDecodingTest extends AbstractTestCase
{
    public function testResponseDataIsRawStringWhenJsonDecodingIsDisabled(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $response = (new PlainApi($client))->raw()->fetch();

        $this->assertSame('{"id":1,"name":"John"}', $response->data());
    }

    public function testResponseDataIsDecodedWhenJsonDecodingIsEnabled(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{"id":1,"name":"John"}'));

        $response = (new JsonApi($client))->raw()->fetch();

        $this->assertSame(['id' => 1, 'name' => 'John'], $response->data());
    }

    public function testEmptyJsonResponseBodyDecodesToNull(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: ''));

        $response = (new JsonApi($client))->raw()->fetch();

        $this->assertNull($response->data());
    }

    public function testInvalidJsonThrowsWhenJsonDecodingIsEnabled(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{invalid-json'));

        $this->expectException(\JsonException::class);

        (new JsonApi($client))->raw()->fetch();
    }
}
