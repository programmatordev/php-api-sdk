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
        private readonly ResolverInterface $resolver,
        private readonly ?string $friendsUrl = null,
        private readonly ?string $managerUrl = null
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
            resolver: $context->resolver(),
            friendsUrl: $data['friends']['url'] ?? null,
            managerUrl: $data['manager']['url'] ?? null
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

    /**
     * @return User[]
     */
    public function friends(): array
    {
        if ($this->friendsUrl === null) {
            return [];
        }

        return $this->resolver->collection($this->friendsUrl, User::class, key: 'data');
    }

    public function manager(): ?User
    {
        if ($this->managerUrl === null) {
            return null;
        }

        return $this->resolver->entity($this->managerUrl, User::class);
    }
}
