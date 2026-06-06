<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Context;
use ProgrammatorDev\Api\Response;
use ProgrammatorDev\Api\ResponseEnvelopeInterface;

class UserEnvelope implements ResponseEnvelopeInterface
{
    public function __construct(
        private readonly User $user,
        private readonly int $statusCode,
        private readonly ?string $timezone = null
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        return new static(
            user: $response->entity(User::class, key: 'data'),
            statusCode: $response->raw()->getStatusCode(),
            timezone: $context?->config()->get('timezone')
        );
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
}
