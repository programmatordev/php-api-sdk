<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;
use ProgrammatorDev\Api\Contract\ResolverInterface;

class LinkedUser implements EntityInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $friendUrl,
        private readonly ResolverInterface $resolver
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        if ($context === null) {
            throw new \RuntimeException('Linked user requires API context.');
        }

        return new static(
            id: $data['id'],
            name: $data['name'],
            friendUrl: $data['friend']['url'],
            resolver: $context->resolver()
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

    public function friend(): User
    {
        return $this->resolver->entity($this->friendUrl, User::class);
    }
}
