<?php

namespace ProgrammatorDev\Api\Test\Fixture\Simple;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\ResponseEnvelopeInterface;
use ProgrammatorDev\Api\Response\Response;

class SimpleResponse implements ResponseEnvelopeInterface
{
    public function __construct(
        private readonly SimpleEntity $entity,
        private readonly int $statusCode,
        private readonly ?string $locale = null
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        return new static(
            entity: $response->entity(SimpleEntity::class),
            statusCode: $response->raw()->getStatusCode(),
            locale: $context?->config()->get('locale')
        );
    }

    public function getEntity(): SimpleEntity
    {
        return $this->entity;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
