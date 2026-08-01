# Resources

`Resource` is the base class for endpoint groups.

`Resource` keeps the SDK-user-facing domain surface small. SDK resource classes call `endpoint()` to start an endpoint request builder.

## Resource Configuration Overrides

> **Available since version 3.1.0.**

### `withConfig()`

```php
withConfig(array $values): static
```

Returns a cloned resource with configuration values that override API-wide
configuration for that resource chain.

```php
$users = $api
    ->users()
    ->withConfig(['timezone' => 'Europe/Lisbon'])
    ->all();
```

The override is available through the resource's scoped runtime and the request
context used by hooks, errors, responses, entities, collections, and envelopes.
It does not mutate the API-wide configuration or automatically add query
parameters or headers.

Repeated calls preserve unrelated values. When the same key is supplied more
than once, the later value wins. Reusing the configured resource applies its
overrides to every request made through that cloned resource.

See [Resource Authoring: Resource-Local Configuration](04-resource-authoring.md#resource-local-configuration)
for SDK-author helpers, request mapping, scope, and precedence.

## Endpoint Builder

### `endpoint()`

```php
endpoint(): Endpoint
```

Protected SDK-author helper.

Returns a fresh endpoint request builder.

```php
return $this
    ->endpoint()
    ->get('/users')
    ->raw();
```

## Endpoint Body Helpers

Endpoint body helpers are immutable and return a cloned endpoint builder.

### `json()`

```php
json(array $data): static
```

Sets a JSON request body and `Content-Type: application/json`.

```php
return $this
    ->endpoint()
    ->json(['name' => 'John'])
    ->post('/users')
    ->entity(User::class);
```

### `form()`

```php
form(array $data): static
```

Sets a form-encoded request body and `Content-Type: application/x-www-form-urlencoded`.

```php
return $this
    ->endpoint()
    ->form(['name' => 'John Doe'])
    ->post('/users')
    ->entity(User::class);
```

### `body()`

```php
body(mixed $body): static
```

Sets a raw string, stream, or null request body.

```php
return $this
    ->endpoint()
    ->body($stream)
    ->post('/uploads')
    ->raw();
```

Passing an array throws. Use `json()` or `form()` for array data.

## Endpoint Query And Headers

### `query()`

```php
query(string $name, mixed $value): static
```

Sets one endpoint-local query option.

```php
return $this
    ->endpoint()
    ->query('active', true)
    ->get('/users')
    ->collection(User::class, key: 'data');
```

### `queries()`

```php
queries(array $query): static
```

Sets multiple endpoint-local query options.

```php
return $this
    ->endpoint()
    ->queries(['active' => true, 'locale' => 'pt'])
    ->get('/users')
    ->collection(User::class, key: 'data');
```

### `header()`

```php
header(string $name, mixed $value): static
```

Sets one endpoint-local header.

```php
return $this
    ->endpoint()
    ->header('X-Upload-Type', 'avatar')
    ->body($stream)
    ->post('/uploads')
    ->raw();
```

### `headers()`

```php
headers(array $headers): static
```

Sets multiple endpoint-local headers.

```php
return $this
    ->endpoint()
    ->headers(['X-Upload-Type' => 'avatar'])
    ->body($stream)
    ->post('/uploads')
    ->raw();
```

### Backed Enum Values

> **Available since version 3.1.0.**

`query()`, `queries()`, `header()`, and `headers()` accept string- and
integer-backed enums. The request uses each enum's scalar value, including in
nested query arrays and header value lists:

```php
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
    ->get('/users');
```

The same normalization applies to values configured through API-level
`defaultQuery()`, `defaultQueries()`, `defaultHeader()`, and `defaultHeaders()`.
Unit enums are not supported as request values; pass an explicit scalar value
instead.

## Endpoint HTTP Methods

Endpoint HTTP helpers execute the request immediately and return `Response`:

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

All helpers accept:

```php
string $path
array $pathParams = []
```

Use `query()`, `queries()`, `header()`, and `headers()` to configure request-local query parameters and headers before calling the HTTP helper.

## Endpoint Cache Defaults

SDK authors can configure endpoint-specific cache defaults on the endpoint builder:

```php
return $this
    ->endpoint()
    ->cache(fn (CacheBuilder $cache) => $cache->defaultTtl(60))
    ->get('/users')
    ->collection(User::class, key: 'data');
```

Endpoint cache defaults are immutable and apply only to that request. They require API-level cache configuration because the global cache setup provides the PSR-6 pool.

## Resource Cache Overrides

`withCache()` lets SDK users override cache behavior for one resource chain while keeping query, headers, body, and verbs inside `Endpoint`.

```php
$users = $api
    ->users()
    ->withCache(fn (CacheBuilder $cache) => $cache->defaultTtl(30))
    ->all();
```

This override is immutable and applies only to the chained resource instance. It requires API-level cache configuration because the global cache setup provides the PSR-6 pool.

See [Cache](09-cache.md) for endpoint cache defaults, merge order, and the API-level cache requirement.

## Navigation

- Previous: [Resource Authoring](04-resource-authoring.md)
- Next: [Responses](06-responses.md)
