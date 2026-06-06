<?php

namespace ProgrammatorDev\Api\Test\Unit;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Builder\ResponseBuilder;
use ProgrammatorDev\Api\ResponseDecoder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use RuntimeException;
use SimpleXMLElement;

class ResponseDecoderTest extends AbstractTestCase
{
    public function testDecodeReturnsRawBodyWhenJsonDecodingIsDisabled(): void
    {
        $decoder = new ResponseDecoder(new ResponseBuilder());

        $this->assertSame('{"ok":true}', $decoder->decode(new Response(body: '{"ok":true}')));
    }

    public function testDecodeReturnsArrayWhenJsonDecodingIsEnabled(): void
    {
        $builder = (new ResponseBuilder())->json();
        $decoder = new ResponseDecoder($builder);

        $this->assertSame(['ok' => true], $decoder->decode(new Response(body: '{"ok":true}')));
    }

    public function testDecodeReturnsNullForEmptyJsonBody(): void
    {
        $builder = (new ResponseBuilder())->json();
        $decoder = new ResponseDecoder($builder);

        $this->assertNull($decoder->decode(new Response(body: '')));
    }

    public function testDecodeThrowsForInvalidJsonBody(): void
    {
        $builder = (new ResponseBuilder())->json();
        $decoder = new ResponseDecoder($builder);

        $this->expectException(\JsonException::class);

        $decoder->decode(new Response(body: '{invalid-json'));
    }

    public function testDecodeReturnsXmlElementWhenXmlDecodingIsEnabled(): void
    {
        $builder = (new ResponseBuilder())->xml();
        $decoder = new ResponseDecoder($builder);

        $data = $decoder->decode(new Response(body: '<user><id>1</id><name>John</name></user>'));

        $this->assertInstanceOf(SimpleXMLElement::class, $data);
        $this->assertSame('1', (string) $data->id);
        $this->assertSame('John', (string) $data->name);
    }

    public function testDecodeReturnsNullForEmptyXmlBody(): void
    {
        $builder = (new ResponseBuilder())->xml();
        $decoder = new ResponseDecoder($builder);

        $this->assertNull($decoder->decode(new Response(body: '')));
    }

    public function testDecodeThrowsForInvalidXmlBody(): void
    {
        $builder = (new ResponseBuilder())->xml();
        $decoder = new ResponseDecoder($builder);

        $this->expectException(RuntimeException::class);

        $decoder->decode(new Response(body: '<invalid'));
    }

    public function testDecodeReturnsCustomDecoderData(): void
    {
        $builder = (new ResponseBuilder())->custom(function (Response $response): array {
            return [
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ];
        });

        $decoder = new ResponseDecoder($builder);

        $this->assertSame([
            'status' => 202,
            'body' => 'accepted',
        ], $decoder->decode(new Response(status: 202, body: 'accepted')));
    }

    public function testChangingFormatClearsCustomDecoder(): void
    {
        $builder = (new ResponseBuilder())
            ->custom(fn (Response $response): string => 'custom')
            ->raw();

        $decoder = new ResponseDecoder($builder);

        $this->assertSame('raw-body', $decoder->decode(new Response(body: 'raw-body')));
    }
}
