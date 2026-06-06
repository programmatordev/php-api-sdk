<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Fixture\PlainApi;
use ProgrammatorDev\Api\Test\Fixture\XmlApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use SimpleXMLElement;

class ResponseDecodingTest extends AbstractTestCase
{
    public function testResponseDataIsRawStringWhenJsonDecodingIsDisabled(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        $response = (new PlainApi($client))->raw()->fetch();

        $this->assertSame('{"id":1,"name":"John"}', $response->data());
    }

    public function testResponseDataIsDecodedWhenJsonDecodingIsEnabled(): void
    {
        $client = $this->mockClient(new Response(body: '{"id":1,"name":"John"}'));

        $response = (new JsonApi($client))->raw()->fetch();

        $this->assertSame(['id' => 1, 'name' => 'John'], $response->data());
    }

    public function testEmptyJsonResponseBodyDecodesToNull(): void
    {
        $client = $this->mockClient(new Response(body: ''));

        $response = (new JsonApi($client))->raw()->fetch();

        $this->assertNull($response->data());
    }

    public function testInvalidJsonThrowsWhenJsonDecodingIsEnabled(): void
    {
        $client = $this->mockClient(new Response(body: '{invalid-json'));

        $this->expectException(\JsonException::class);

        (new JsonApi($client))->raw()->fetch();
    }

    public function testResponseDataIsDecodedWhenXmlDecodingIsEnabled(): void
    {
        $client = $this->mockClient(new Response(body: '<user><id>1</id><name>John</name></user>'));

        $response = (new XmlApi($client))->raw()->fetch();

        $this->assertInstanceOf(SimpleXMLElement::class, $response->data());
        $this->assertSame('1', (string) $response->data()->id);
        $this->assertSame('John', (string) $response->data()->name);
    }

    public function testEmptyXmlResponseBodyDecodesToNull(): void
    {
        $client = $this->mockClient(new Response(body: ''));

        $response = (new XmlApi($client))->raw()->fetch();

        $this->assertNull($response->data());
    }

    public function testInvalidXmlThrowsWhenXmlDecodingIsEnabled(): void
    {
        $client = $this->mockClient(new Response(body: '<invalid'));

        $this->expectException(\RuntimeException::class);

        (new XmlApi($client))->raw()->fetch();
    }
}
