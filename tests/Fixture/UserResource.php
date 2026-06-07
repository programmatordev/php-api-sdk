<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use ProgrammatorDev\Api\Resource;
use ProgrammatorDev\Api\Response\Response;
use Psr\Http\Message\StreamInterface;

class UserResource extends Resource
{
    public function sendWithVerb(string $verb): void
    {
        match ($verb) {
            'GET' => $this->endpoint()->get('/users'),
            'POST' => $this->endpoint()->post('/users'),
            'PUT' => $this->endpoint()->put('/users/{id}', ['id' => 1]),
            'PATCH' => $this->endpoint()->patch('/users/{id}', ['id' => 1]),
            'DELETE' => $this->endpoint()->delete('/users/{id}', ['id' => 1]),
            'HEAD' => $this->endpoint()->head('/users'),
            'OPTIONS' => $this->endpoint()->options('/users'),
            'CONNECT' => $this->endpoint()->connect('/users'),
            'TRACE' => $this->endpoint()->trace('/users'),
        };
    }

    public function createWithJson(array $data): void
    {
        $this->endpoint()->json($data)->post('/users');
    }

    public function createWithForm(array $data): void
    {
        $this->endpoint()->form($data)->post('/users');
    }

    public function createWithBody(string|StreamInterface|null $body): void
    {
        $this->endpoint()->body($body)->post('/users');
    }

    public function createWithInvalidBody(mixed $body): void
    {
        $this->endpoint()->body($body)->post('/users');
    }

    public function createWithEndpointCache(array $data): Response
    {
        return $this
            ->endpoint()
            ->cache(fn($cache) => $cache->methods(['POST']))
            ->json($data)
            ->post('/users');
    }

    public function createWithChainedEndpointCache(array $data): Response
    {
        return $this
            ->endpoint()
            ->cache(fn($cache) => $cache->methods(['POST']))
            ->cache(fn($cache) => $cache->methods(['GET']))
            ->json($data)
            ->post('/users');
    }

    public function all(): array
    {
        return $this
            ->endpoint()
            ->get('/users')
            ->collection(User::class, key: 'data');
    }

    public function find(int|string $id): User
    {
        return $this
            ->endpoint()
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    public function findFromEnvelope(int|string $id): User
    {
        return $this
            ->endpoint()
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class, key: 'data');
    }

    public function findEnvelope(int|string $id): UserEnvelope
    {
        return $this
            ->endpoint()
            ->get('/users/{id}', ['id' => $id])
            ->envelope(UserEnvelope::class);
    }

    public function findWithEndpointLocale(int|string $id, string $locale): User
    {
        return $this
            ->endpoint()
            ->get('/users/{id}', ['id' => $id], ['locale' => $locale])
            ->entity(User::class);
    }

    public function findWithEndpointOptions(int|string $id): User
    {
        return $this
            ->endpoint()
            ->queries(['active' => true])
            ->headers(['X-Tenant' => 'acme'])
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    public function findWithConfiguredTimezone(int|string $id): User
    {
        return $this
            ->endpoint()
            ->query('timezone', $this->api->config()->get('timezone'))
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    public function findWithActive(int|string $id, bool $active = true): User
    {
        return $this
            ->endpoint()
            ->query('active', $active)
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    public function findWithEmptyQuery(int|string $id): User
    {
        return $this
            ->endpoint()
            ->query('empty', null)
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }
}
