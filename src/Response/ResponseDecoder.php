<?php

namespace ProgrammatorDev\Api\Response;

use ProgrammatorDev\Api\Builder\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use SimpleXMLElement;

final class ResponseDecoder
{
    public function __construct(
        private readonly ResponseBuilder $responseBuilder
    ) {}

    /**
     * @throws \JsonException
     * @throws RuntimeException
     */
    public function decode(ResponseInterface $response): mixed
    {
        $response->getBody()->rewind();
        $contents = $response->getBody()->getContents();

        return match ($this->responseBuilder->format()) {
            ResponseFormat::Raw => $contents,
            ResponseFormat::Json => $this->decodeJson($contents),
            ResponseFormat::Xml => $this->decodeXml($contents),
            ResponseFormat::Custom => $this->decodeCustom($response),
        };
    }

    /**
     * @throws \JsonException
     */
    private function decodeJson(string $contents): mixed
    {
        return $contents === ''
            ? null
            : json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }

    private function decodeXml(string $contents): ?SimpleXMLElement
    {
        if ($contents === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string($contents);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$xml instanceof SimpleXMLElement) {
            $message = $errors[0]->message ?? 'Invalid XML response body.';

            throw new RuntimeException(trim($message));
        }

        return $xml;
    }

    private function decodeCustom(ResponseInterface $response): mixed
    {
        $decoder = $this->responseBuilder->customDecoder();

        if ($decoder === null) {
            throw new RuntimeException('A custom response decoder must be configured.');
        }

        return $decoder($response);
    }
}
