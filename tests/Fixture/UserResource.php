<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Resource;

class UserResource extends Resource
{
    public function all(): array
    {
        return $this
            ->get('/users')
            ->collection(User::class, key: 'data');
    }

    public function find(int|string $id): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    public function findFromEnvelope(int|string $id): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class, key: 'data');
    }

    public function findEnvelope(int|string $id): UserEnvelope
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->as(UserEnvelope::class);
    }

    public function findWithEndpointLocale(int|string $id, string $locale): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id], ['locale' => $locale])
            ->entity(User::class);
    }
}
