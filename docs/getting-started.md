# Getting Started

These examples describe the work-in-progress `v3.0` authoring API.

## Install

```bash
composer require programmatordev/php-api-sdk
```

You also need compatible PSR-18 and PSR-17 implementations. The package uses PHP-HTTP discovery, so SDK packages can choose the implementations they want to require or suggest.

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
            ->queryDefaults(['locale' => 'en'])
            ->headerDefaults(['Accept' => 'application/json']);

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
use ProgrammatorDev\Api\Context;
use ProgrammatorDev\Api\EntityInterface;

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

Resources group endpoint methods. Use protected HTTP helpers and response mapping helpers to keep the endpoint code compact.

```php
use ProgrammatorDev\Api\Resource;

final class UserResource extends Resource
{
    public function find(int $id): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }

    /**
     * @return User[]
     */
    public function all(): array
    {
        return $this
            ->get('/users')
            ->collection(User::class, key: 'data');
    }

    public function create(string $name): User
    {
        return $this
            ->json(['name' => $name])
            ->post('/users')
            ->entity(User::class, key: 'data');
    }
}
```

Path parameters are passed as the second argument to the HTTP helper:

```php
$this->get('/users/{id}', ['id' => $id]);
```

Endpoint-specific query parameters can be passed as the third argument:

```php
$this->get('/users/{id}', ['id' => $id], ['locale' => 'pt']);
```

Reusable query and header options are fluent and immutable:

```php
$activeUsers = $api
    ->users()
    ->query('active', true)
    ->all();
```

## Map Enveloped Responses

If an API returns metadata, pagination, or any custom envelope, create a response envelope class.

```php
use ProgrammatorDev\Api\Context;
use ProgrammatorDev\Api\Response;
use ProgrammatorDev\Api\ResponseEnvelopeInterface;

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
        ->get('/users/{id}', ['id' => $id])
        ->as(UserResponse::class);
}
```

## Next Steps

- Read [Resource Authoring](resource-authoring.md) for the full resource API.
