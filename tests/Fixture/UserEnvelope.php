<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Response;
use ProgrammatorDev\Api\ResponseEnvelope;

class UserEnvelope implements ResponseEnvelope
{
    public function __construct(
        private readonly User $user,
        private readonly int $statusCode
    ) {}

    public static function fromResponse(Response $response): static
    {
        return new static(
            user: $response->entity(User::class, key: 'data'),
            statusCode: $response->raw()->getStatusCode()
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
}
