# Responses

Response mapping covers decoded data, raw PSR responses, entities, collections, custom response envelopes, and hydration context.

## `Response`

`Response` wraps decoded response data and the raw PSR response.

### `data(): mixed`

Returns response data.

When `responses()->json()` is enabled on the API, JSON bodies are decoded into arrays, empty bodies become `null`, and invalid JSON throws `JsonException`.

When `responses()->xml()` is enabled, XML bodies are decoded into `SimpleXMLElement`, empty bodies become `null`, and invalid XML throws `RuntimeException`.

When `responses()->custom()` is enabled, the configured callable receives the raw PSR response and returns the value used as `Response::data()`.

When no response format is configured, this returns the raw response body string.

```php
$data = $response->data();
```

### `raw(): ResponseInterface`

Returns the raw PSR response.

```php
$status = $response->raw()->getStatusCode();
```

### `entity(string $class, ?string $key = null): EntityInterface`

Maps decoded response data to an entity class.

```php
return $this
    ->endpoint()
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class, key: 'data');
```

The class must implement `EntityInterface`.

### `collection(string $class, ?string $key = null): array`

Maps list data to a plain array of entities.

```php
return $this
    ->endpoint()
    ->get('/users')
    ->collection(User::class, key: 'data');
```

### `envelope(string $class): ResponseEnvelopeInterface`

Maps the response to a custom envelope.

```php
return $this
    ->endpoint()
    ->get('/users/{id}', ['id' => $id])
    ->envelope(UserResponse::class);
```

The class must implement `ResponseEnvelopeInterface`.

## `EntityInterface`

Entities used by response mapping must implement:

```php
public static function fromArray(array $data, ?Context $context = null): static;
```

`fromArray()` is the mapping boundary for an entity. The package passes decoded response data to it; the SDK author decides how payload keys become constructor arguments, value objects, or derived values.

## `ResponseEnvelopeInterface`

Response envelopes used by `Response::envelope()` must implement:

```php
public static function fromResponse(Response $response, ?Context $context = null): static;
```

## `Context`

`Context` carries SDK config into response mapping.

SDK users do not fetch context from `Response`. The package passes context into entity and envelope hydration methods:

```php
EntityInterface::fromArray(array $data, ?Context $context = null)
ResponseEnvelopeInterface::fromResponse(Response $response, ?Context $context = null)
```

### `config(): Config`

Returns the SDK config available while hydrating entities or response envelopes.

```php
$timezone = $context?->config()->get('timezone');
```

## `ErrorContext`

`ErrorContext` is passed to configured error handlers.

```php
$this->errors()->status(404, NotFoundException::class);
```

```php
$this->errors()->statuses([
    401 => UnauthorizedException::class,
    404 => NotFoundException::class,
]);
```

```php
$this->errors()->status(404, function (ErrorContext $context): Throwable {
    return new NotFoundException($context->response()->data()['message']);
});
```

```php
$this->errors()->when(function (ErrorContext $context): ?Throwable {
    if (($context->response()->data()['code'] ?? null) !== 'invalid_api_key') {
        return null;
    }

    return new InvalidApiKeyException($context->response()->data()['message']);
});
```

It exposes:

- `response(): Response`
- `apiContext(): Context`
- `statusCode(): int`

## Navigation

- Previous: [Resources](05-resources.md)
- Next: [Authentication](07-authentication.md)
