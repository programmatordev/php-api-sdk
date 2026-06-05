<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Resource;

class UserResource extends Resource
{
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

    public function findWithEndpointLocale(int|string $id, string $locale): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id], ['locale' => $locale])
            ->entity(User::class);
    }
}
