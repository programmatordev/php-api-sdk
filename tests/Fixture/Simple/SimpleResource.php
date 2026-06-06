<?php

namespace ProgrammatorDev\Api\Test\Fixture\Simple;

use ProgrammatorDev\Api\Resource;

class SimpleResource extends Resource
{
    public function find(int|string $id): SimpleEntity
    {
        return $this
            ->get('/items/{id}', ['id' => $id])
            ->entity(SimpleEntity::class);
    }

    public function findResponse(int|string $id): SimpleResponse
    {
        return $this
            ->get('/items/{id}', ['id' => $id])
            ->envelope(SimpleResponse::class);
    }

    /**
     * @return SimpleEntity[]
     */
    public function all(): array
    {
        return $this
            ->get('/items')
            ->collection(SimpleEntity::class, key: 'data');
    }
}
