# Getting Started

These examples show the current SDK authoring API.

## Install

```bash
composer require programmatordev/php-api-sdk
```

The package uses PHP-HTTP discovery for PSR-18 clients and PSR-17 factories. When the `php-http/discovery` Composer plugin is enabled, missing implementations can be installed automatically. SDK packages may still require or suggest concrete implementations when they want tighter control over the default HTTP stack.

## Create An API Class

The API class is the SDK facade. It configures shared options and exposes purpose-built resources.

```php
use ProgrammatorDev\Api\Api;

final class ExampleApi extends Api
{
    public function __construct(string $apiKey)
    {
        parent::__construct();

        $this
            ->baseUrl('https://api.example.com')
            ->defaultQueries(['locale' => 'en'])
            ->defaultHeaders(['Accept' => 'application/json']);

        $this->auth()->query('api_key', $apiKey);
    }

    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
```

The final SDK user works with `users()`, not raw request execution:

```php
$user = $api->users()->find(1);
```

## Create An Entity

Entities are typed response objects. Classes used with `Response::entity()` and `Response::collection()` must implement `EntityInterface`.

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

Resources group endpoint methods. Use `endpoint()` to start an endpoint request builder, then map the response.

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

Path parameters are passed as the second argument to the HTTP helper:

```php
$this->endpoint()->get('/users/{id}', ['id' => $id]);
```

Endpoint-specific query parameters can be passed as the third argument:

```php
$this->endpoint()->get('/users/{id}', ['id' => $id], ['locale' => 'pt']);
```

SDK authors decide how SDK users customize requests. Often a method argument is enough:

```php
final class UserResource extends Resource
{
    public function all(bool $active = true): array
    {
        return $this
            ->endpoint()
            ->query('active', $active)
            ->get('/users')
            ->collection(User::class, key: 'data');
    }
}

$activeUsers = $api->users()->all(active: true);
```

## Map Enveloped Responses

If an API returns metadata, pagination, or any custom envelope, create a response envelope class.

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Contract\ResponseEnvelopeInterface;

final class UserResponse implements ResponseEnvelopeInterface
{
    public function __construct(
        public readonly User $user,
        public readonly int $statusCode,
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        return new self(
            user: $response->entity(User::class, key: 'data'),
            statusCode: $response->raw()->getStatusCode(),
        );
    }
}
```

Then return it from the resource:

```php
public function findWithMeta(int $id): UserResponse
{
    return $this
        ->endpoint()
        ->get('/users/{id}', ['id' => $id])
        ->envelope(UserResponse::class);
}
```

## Navigation

- Previous: [Documentation](00-index.md)
- Next: [Design Approach](02-design-approach.md)
