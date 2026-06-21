# Getting Started

This guide builds a small SDK with one entity, one resource, and one API facade.

## Install

```bash
composer require programmatordev/php-api-sdk
```

The package uses PHP-HTTP discovery for PSR-18 clients and PSR-17 factories. When the `php-http/discovery` Composer plugin is enabled, missing implementations can be installed automatically. SDK packages may still require or suggest concrete implementations when they want tighter control over the default HTTP stack.

## Create An Entity

Entities are typed response objects. Classes used with `Response::entity()` and `Response::collection()` must implement `EntityInterface`.

`fromArray()` is the SDK author's mapping boundary: it defines how decoded API data becomes the entity.

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;

final class User implements EntityInterface
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
```

## Create A Resource

Resources group endpoint methods. Use `endpoint()` to start a request, execute it, and map the response.

```php
use ProgrammatorDev\Api\Resource;

final class UserResource extends Resource
{
    public function find(int $id): User
    {
        return $this
            ->endpoint()
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        return $this
            ->endpoint()
            ->get('/users')
            ->collection(User::class, key: 'data');
    }

    public function create(string $name): User
    {
        return $this
            ->endpoint()
            ->json(['name' => $name])
            ->post('/users')
            ->entity(User::class, key: 'data');
    }
}
```

See [Resource Authoring](04-resource-authoring.md) for query parameters, headers, request bodies, cache overrides, and API-specific fluent chains.

## Create An API Class

The API class is the SDK facade. It configures shared options and exposes purpose-built resources.

```php
use ProgrammatorDev\Api\Api;

final class ExampleApi extends Api
{
    public function __construct(string $apiKey)
    {
        parent::__construct();

        $this->baseUrl('https://api.example.com');
        $this->auth()->query('api_key', $apiKey);
    }

    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
```

SDK users work with resources and endpoint methods, not raw request execution:

```php
$api = new ExampleApi('secret');

$user = $api->users()->find(1);
$users = $api->users()->all();
```

## Map Envelopes

If an API returns metadata, pagination, or any custom envelope, create an envelope class.

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;

final class UserEnvelope implements EnvelopeInterface
{
    public function __construct(
        /** @var User[] */
        public readonly array $users,
        public readonly int $page,
        public readonly int $totalPages,
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        $data = $response->data();

        return new self(
            users: $response->collection(User::class, key: 'data'),
            page: $data['pagination']['page'],
            totalPages: $data['pagination']['total_pages'],
        );
    }
}
```

Then return it from the resource:

```php
public function all(int $page = 1): UserEnvelope
{
    return $this
        ->endpoint()
        ->query('page', $page)
        ->get('/users')
        ->envelope(UserEnvelope::class);
}
```

## Navigation

- Previous: [Documentation](00-index.md)
- Next: [Design Approach](02-design-approach.md)
