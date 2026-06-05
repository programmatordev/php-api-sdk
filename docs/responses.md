# Responses

Response mapping covers decoded data, raw PSR responses, entities, collections, custom response envelopes, and hydration context.

## `Response`

`Response` wraps decoded response data and the raw PSR response.

### `data(): mixed`

Returns decoded response data.

```php
$data = $response->data();
```

### `raw(): ResponseInterface`

Returns the raw PSR response.

```php
$status = $response->raw()->getStatusCode();
```

### `entity(string $class, ?string $key = null): Entity`

Maps decoded response data to an entity class.

```php
return $this
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class, key: 'data');
```

The class must implement `Entity`.

### `collection(string $class, ?string $key = null): array`

Maps list data to a plain array of entities.

```php
return $this
    ->get('/users')
    ->collection(User::class, key: 'data');
```

### `as(string $class): ResponseEnvelope`

Maps the response to a custom envelope.

```php
return $this
    ->get('/users/{id}', ['id' => $id])
    ->as(UserResponse::class);
```

The class must implement `ResponseEnvelope`.

## `Entity`

Entities used by response mapping must implement:

```php
public static function fromArray(array $data, ?Context $context = null): static;
```

## `ResponseEnvelope`

Response envelopes used by `Response::as()` must implement:

```php
public static function fromResponse(Response $response, ?Context $context = null): static;
```

## `Context`

`Context` carries SDK config into response mapping.

SDK users do not fetch context from `Response`. The package passes context into entity and envelope hydration methods:

```php
Entity::fromArray(array $data, ?Context $context = null)
ResponseEnvelope::fromResponse(Response $response, ?Context $context = null)
```

### `config(): Config`

Returns the SDK config available while hydrating entities or response envelopes.

```php
$timezone = $context?->config()->get('timezone');
```
