<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Entity;

class User implements Entity
{
    public function __construct(
        private readonly int $id,
        private readonly string $name
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: $data['id'],
            name: $data['name']
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
}
