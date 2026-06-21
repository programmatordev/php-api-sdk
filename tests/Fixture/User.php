<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;

class User implements EntityInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $timezone = null
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new static(
            id: $data['id'],
            name: $data['name'],
            timezone: $context?->config()->get('timezone')
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
}
