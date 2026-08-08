# Resource Authoring

Resources are the main authoring surface for SDK developers. They group endpoints and hide low-level request execution from the final SDK user.

The typical endpoint method should stay compact:

```php
final class UserResource extends Resource
{
    public function find(int $id): User
    {
        return $this
            ->endpoint()
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

`Api::resource()` creates a fresh resource instance. Resource-chain infrastructure overrides, such as `withCache()`, are immutable, so fluent customizations do not leak into later calls.

## Resource Constructor Dependencies

> **Available since version 3.3.0.**

Use `resourceWith()` when a resource needs typed, SDK-author-owned data that
should not be placed in the shared SDK config. The runtime is injected
automatically as the first constructor argument.

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Resource;
use ProgrammatorDev\Api\Runtime;

final class ExampleApi extends Api
{
    public function __construct(
        private readonly string $apiKey,
    ) {
        parent::__construct();
    }

    public function assets(): AssetResource
    {
        return $this->resourceWith(
            AssetResource::class,
            apiKey: $this->apiKey,
        );
    }
}

final class AssetResource extends Resource
{
    public function __construct(
        Runtime $runtime,
        private readonly string $apiKey,
    ) {
        parent::__construct($runtime);
    }

    public function url(string $file): string
    {
        return sprintf(
            'https://cdn.example.com/assets/%s?key=%s',
            rawurlencode($file),
            rawurlencode($this->apiKey),
        );
    }
}
```

This keeps the dependency private to the concrete API and resource. Use
`Config` for SDK options that should also be available to contexts, entities,
envelopes, hooks, and error handlers. Credentials used for HTTP authentication
should still be configured through `auth()`.

## Endpoint Requests

Use `endpoint()` inside resource methods to create the request builder:

```php
$endpoint = $this->endpoint();

$endpoint->get('/users');
$endpoint->post('/users');
$endpoint->put('/users/{id}', ['id' => $id]);
$endpoint->patch('/users/{id}', ['id' => $id]);
$endpoint->delete('/users/{id}', ['id' => $id]);
$endpoint->head('/users');
$endpoint->options('/users');
$endpoint->connect('/users');
$endpoint->trace('/users');
```

Each helper executes the request immediately and returns a `Response` wrapper.

Endpoint-specific query parameters are configured on the endpoint builder:

```php
return $this
    ->endpoint()
    ->query('locale', 'pt')
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class);
```

Path parameters are encoded with `rawurlencode`.

## Query And Headers

Use endpoint modifiers for request-local options:

```php
return $this
    ->endpoint()
    ->query('active', true)
    ->header('X-Tenant', $tenant)
    ->get('/users')
    ->collection(User::class, key: 'data');
```

Multiple values can be set with `queries()` and `headers()`:

```php
return $this
    ->endpoint()
    ->queries(['active' => true, 'locale' => 'pt'])
    ->headers(['X-Tenant' => $tenant])
    ->get('/users')
    ->collection(User::class, key: 'data');
```

### Backed Enum Values

> **Available since version 3.1.0.**

String- and integer-backed enums can be passed directly as query parameters or
header values:

```php
enum Status: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
}

enum Visibility: int
{
    case PUBLIC = 1;
}

return $this
    ->endpoint()
    ->queries([
        'status' => Status::ACTIVE,
        'filter' => ['visibility' => Visibility::PUBLIC],
    ])
    ->headers([
        'X-Status' => Status::ACTIVE,
        'X-Allowed-Statuses' => [Status::ACTIVE, Status::PENDING],
    ])
    ->get('/users')
    ->collection(User::class, key: 'data');
```

The backed values are normalized recursively after API defaults and endpoint
options are merged. This applies to endpoint values, API-level defaults,
nested query arrays, and header value lists. Header values are converted to
strings as required by PSR-7. Unit enums are not supported as request values;
pass an explicit scalar value instead.

SDK-user customization should be explicit in the resource method API. If a method argument is enough, prefer that over hidden resource state:

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
```

SDK users call the public method chosen by the SDK author:

```php
$users = $api->users()->all(active: true);
```

Query merge order is:

```text
API defaults < endpoint options < endpoint method query argument
```

Null query values are omitted. Boolean and array query values use PHP's standard `http_build_query` behavior.

## Request Bodies

Use explicit body helpers for structured request data:

```php
return $this
    ->endpoint()
    ->json(['name' => 'John'])
    ->post('/users')
    ->entity(User::class);
```

`json()` encodes the array as JSON and sets `Content-Type: application/json`.

```php
return $this
    ->endpoint()
    ->form(['name' => 'John Doe'])
    ->post('/users')
    ->entity(User::class);
```

`form()` encodes the array with `http_build_query` and sets `Content-Type: application/x-www-form-urlencoded`.

Use `body()` for raw string or PSR-7 stream bodies:

```php
return $this
    ->endpoint()
    ->body($stream)
    ->post('/uploads')
    ->raw();
```

`body()` does not guess the content type. Passing an array to `body()` throws; use `json()` or `form()` instead.

## Response Mapping

Use `entity()` when the endpoint returns one typed object:

```php
return $this
    ->endpoint()
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class);
```

Entities must implement `EntityInterface`:

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;

final class User implements EntityInterface
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

Treat `fromArray()` as the entity's mapping boundary. It keeps resource methods focused on requests and lets each entity own how decoded API payloads become typed PHP values.

Use the optional `key` argument when the object is nested inside an envelope:

```php
return $this
    ->endpoint()
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class, key: 'data');
```

Use `collection()` when the endpoint returns a list:

```php
return $this
    ->endpoint()
    ->get('/users')
    ->collection(User::class, key: 'data');
```

`collection()` returns a plain array of entities.

Use `envelope()` when the response carries metadata, pagination, or any API-specific envelope:

```php
return $this
    ->endpoint()
    ->get('/users/{id}', ['id' => $id])
    ->envelope(UserEnvelope::class);
```

Envelope classes must implement `EnvelopeInterface`:

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;

final class UserEnvelope implements EnvelopeInterface
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

## Context

`Context` carries SDK options into response mapping without passing the full `Api` instance around.

The flow is:

```text
SDK constructor options -> Api config -> Context -> EntityInterface or EnvelopeInterface
```

Start by accepting SDK options and storing them in config:

```php
final class ExampleApi extends Api
{
    public function __construct(string $apiKey, array $options = [])
    {
        parent::__construct();

        $this->baseUrl('https://api.example.com');
        $this->config($options, defaults: [
            'timezone' => 'UTC',
        ]);
    }
}
```

When a response is mapped, the SDK creates a context with that config. The same context is passed to:

- `EntityInterface::fromArray(array $data, ?Context $context = null)`
- `EnvelopeInterface::fromResponse(Response $response, ?Context $context = null)`

Entities can use config values during hydration:

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;

final class User implements EntityInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $timezone,
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            timezone: $context?->config()->get('timezone'),
        );
    }
}
```

Envelopes receive the same context:

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;

final class UserEnvelope implements EnvelopeInterface
{
    public function __construct(
        private readonly User $user,
        private readonly ?string $timezone,
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        return new self(
            user: $response->entity(User::class, key: 'data'),
            timezone: $context?->config()->get('timezone'),
        );
    }
}
```

Keep context usage focused on hydration decisions. Entities should still be data/value objects by default and should not perform hidden network calls.

When an API exposes relationships or pagination as links, an SDK author can opt
into explicit request-backed methods through the context resolver. See
[Resolver](07-resolver.md).

## Resource-Local Configuration

> **Available since version 3.1.0.**

Resource-local configuration lets SDK authors build typed, immutable helpers
for options that affect both a request and its response context:

```php
public function withLocale(string $locale): static
{
    return $this->withConfig([
        'locale' => $locale,
    ]);
}
```

SDK users then get an API-specific fluent method:

```php
$user = $api->users()->withLocale('pt')->find(1);
```

`withConfig()` remains available as the generic escape hatch:

```php
$user = $api
    ->users()
    ->withConfig(['locale' => 'pt'])
    ->find(1);
```

The original resource and API-wide configuration remain unchanged. The
override belongs to the cloned resource, so reusing that resource applies it to
every request made through the clone:

```php
$portugueseUsers = $api->users()->withLocale('pt');

$first = $portugueseUsers->find(1);
$second = $portugueseUsers->find(2);
```

Repeated calls merge their values, and later values win for the same key:

```php
$users = $api
    ->users()
    ->withConfig(['locale' => 'en', 'timezone' => 'UTC'])
    ->withConfig(['locale' => 'pt']);
```

The effective configuration contains `locale=pt` and `timezone=UTC`.
Non-overridden values come from the latest API-wide configuration, including
changes made after the resource was created:

```text
Latest API-wide configuration
-> resource withConfig() overrides
```

Inside a resource, the scoped runtime exposes the effective configuration:

```php
public function find(int $id): User
{
    return $this
        ->endpoint()
        ->query(
            'locale',
            $this->runtime->config()->get('locale'),
        )
        ->get('/users/{id}', ['id' => $id])
        ->entity(User::class);
}
```

Configuration is not automatically converted into query parameters or headers.
The resource author decides how each option maps to an endpoint.

Inside resource methods, read scoped values with
`$this->runtime->config()->get()`. Do not call `set()` or `merge()` on that
scoped `Config`; apply changes by returning a clone through `withConfig()`.
The effective configuration is propagated through request and response hooks,
error handling, response mapping, entities, collections, and envelopes. This
keeps request construction and response interpretation consistent.

## API-Specific Resource Chains

Keep API-specific vocabulary out of the base package. Add it in SDK resources with small fluent methods that use the generic endpoint helpers underneath.

For example, an SDK can expose a reusable status filter without making the generic package know what a status filter is:

```php
trait HasStatusFilter
{
    private ?string $status = null;

    public function withStatus(string $status): static
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }
}
```

Resources that support that API-specific filter can opt in to the trait:

```php
final class UserResource extends Resource
{
    use HasStatusFilter;

    public function all(): array
    {
        return $this
            ->endpoint()
            ->query('status', $this->status)
            ->get('/users')
            ->collection(User::class, key: 'data');
    }
}
```

SDK users get a fluent API-specific chain:

```php
$users = $api
    ->users()
    ->withStatus('active')
    ->all();
```

Use the same pattern for API-specific concepts such as includes, filters,
selects, pagination options, or locale settings. Use `withConfig()` when the
value also affects request context or response interpretation. Clone the
resource directly for other API-specific request state so a configured chain
does not leak into later calls.

## Navigation

- Previous: [API](03-api.md)
- Next: [Resources](05-resources.md)
