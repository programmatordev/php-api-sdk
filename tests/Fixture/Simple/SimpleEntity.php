<?php

namespace ProgrammatorDev\Api\Test\Fixture\Simple;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;

class SimpleEntity implements EntityInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $locale = null,
        private readonly ?string $version = null
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new static(
            id: $data['id'],
            name: $data['name'],
            locale: $context?->config()->get('locale'),
            version: $context?->config()->get('version')
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

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }
}
