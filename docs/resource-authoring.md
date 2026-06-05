# Resource Authoring

Resources are the main authoring surface for SDK developers. They group endpoints and hide low-level request execution from the final SDK user.

The typical endpoint method should stay compact:

```php
final class UserResource extends Resource
{
    public function find(int $id): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }
}
```

SDK users call purpose-built methods:

```php
$user = $api->users()->find(1);
```

## Resources From The API

Concrete SDKs should expose resources through methods on their API class:

```php
final class ExampleApi extends Api
{
    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
```

`Api::resource()` creates a fresh resource instance. Resource option modifiers are immutable, so fluent customizations do not leak into later calls.

## HTTP Methods

Resources expose protected HTTP helpers:

```php
$this->get('/users');
$this->post('/users');
$this->put('/users/{id}', ['id' => $id]);
$this->patch('/users/{id}', ['id' => $id]);
$this->delete('/users/{id}', ['id' => $id]);
$this->head('/users');
$this->options('/users');
```

Each helper executes the request immediately and returns a `Response` wrapper.

Endpoint-specific query parameters can be passed as the third argument:

```php
return $this
    ->get('/users/{id}', ['id' => $id], ['locale' => 'pt'])
    ->entity(User::class);
```

Path parameters are encoded with `rawurlencode`.

## Query And Headers

Use resource modifiers for reusable request options:

```php
return $this
    ->query('active', true)
    ->header('X-Tenant', $tenant)
    ->get('/users')
    ->collection(User::class, key: 'data');
```

Multiple values can be set with `queries()` and `headers()`:

```php
return $this
    ->queries(['active' => true, 'locale' => 'pt'])
    ->headers(['X-Tenant' => $tenant])
    ->get('/users')
    ->collection(User::class, key: 'data');
```

Query merge order is:

```text
API defaults < resource options < endpoint-specific options
```

Null query values are omitted. Boolean and array query values use PHP's standard `http_build_query` behavior.

## Request Bodies

Use explicit body helpers for structured request data:

```php
return $this
    ->json(['name' => 'John'])
    ->post('/users')
    ->entity(User::class);
```

`json()` encodes the array as JSON and sets `Content-Type: application/json`.

```php
return $this
    ->form(['name' => 'John Doe'])
    ->post('/users')
    ->entity(User::class);
```

`form()` encodes the array with `http_build_query` and sets `Content-Type: application/x-www-form-urlencoded`.

Use `body()` for raw string or PSR-7 stream bodies:

```php
return $this
    ->body($stream)
    ->post('/uploads')
    ->raw();
```

`body()` does not guess the content type. Passing an array to `body()` throws; use `json()` or `form()` instead.

## Response Mapping

Use `entity()` when the endpoint returns one typed object:

```php
return $this
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class);
```

Entities must implement `Entity`:

```php
use ProgrammatorDev\Api\Context;

final class User implements Entity
{
    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
```

Use the optional `key` argument when the object is nested inside an envelope:

```php
return $this
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class, key: 'data');
```

Use `collection()` when the endpoint returns a list:

```php
return $this
    ->get('/users')
    ->collection(User::class, key: 'data');
```

`collection()` returns a plain array of entities.

Use `as()` when the response carries metadata, pagination, or any API-specific envelope:

```php
return $this
    ->get('/users/{id}', ['id' => $id])
    ->as(UserResponse::class);
```

Envelope classes must implement `ResponseEnvelope`:

```php
use ProgrammatorDev\Api\Context;

final class UserResponse implements ResponseEnvelope
{
    public function __construct(
        private readonly User $user,
        private readonly int $statusCode,
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

## API-Specific Traits

Keep API-specific vocabulary out of the base package. Add it in SDK packages through traits or API-specific base resources.

For example, an SDK can add includes without making the generic package know what an include is:

```php
trait HasIncludes
{
    public function include(string ...$includes): static
    {
        return $this->query('include', implode(';', $includes));
    }
}
```

Then use it in that SDK's resources:

```php
final class FixtureResource extends Resource
{
    use HasIncludes;

    public function find(int $id): FixtureResponse
    {
        return $this
            ->include('participants', 'league')
            ->get('/fixtures/{id}', ['id' => $id])
            ->as(FixtureResponse::class);
    }
}
```
