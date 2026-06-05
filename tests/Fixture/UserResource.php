<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Resource;
use Psr\Http\Message\StreamInterface;

class UserResource extends Resource
{
    public function sendWithVerb(string $verb): void
    {
        match ($verb) {
            'GET' => $this->get('/users'),
            'POST' => $this->post('/users'),
            'PUT' => $this->put('/users/{id}', ['id' => 1]),
            'PATCH' => $this->patch('/users/{id}', ['id' => 1]),
            'DELETE' => $this->delete('/users/{id}', ['id' => 1]),
            'HEAD' => $this->head('/users'),
            'OPTIONS' => $this->options('/users'),
        };
    }

    public function createWithJson(array $data): void
    {
        $this->json($data)->post('/users');
    }

    public function createWithForm(array $data): void
    {
        $this->form($data)->post('/users');
    }

    public function createWithBody(string|StreamInterface|null $body): void
    {
        $this->body($body)->post('/users');
    }

    public function createWithInvalidBody(mixed $body): void
    {
        $this->body($body)->post('/users');
    }

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

    public function findWithConfiguredTimezone(int|string $id): User
    {
        return $this
            ->query('timezone', $this->config()->get('timezone'))
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }
}
