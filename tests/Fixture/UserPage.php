<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;
use ProgrammatorDev\Api\Contract\ResolverInterface;
use ProgrammatorDev\Api\Response\Response;

class UserPage implements EnvelopeInterface
{
    /**
     * @param User[] $users
     */
    public function __construct(
        private readonly array $users,
        private readonly ?string $nextUrl,
        private readonly ?string $previousUrl,
        private readonly ResolverInterface $resolver
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        if ($context === null) {
            throw new \RuntimeException('User page requires API context.');
        }

        $data = $response->data();

        return new static(
            users: $response->collection(User::class, key: 'data'),
            nextUrl: $data['next'] ?? null,
            previousUrl: $data['previous'] ?? null,
            resolver: $context->resolver()
        );
    }

    /**
     * @return User[]
     */
    public function users(): array
    {
        return $this->users;
    }

    public function next(): ?self
    {
        if ($this->nextUrl === null) {
            return null;
        }

        return $this->resolver->envelope($this->nextUrl, self::class);
    }

    public function previous(): ?self
    {
        if ($this->previousUrl === null) {
            return null;
        }

        return $this->resolver->envelope($this->previousUrl, self::class);
    }
}
