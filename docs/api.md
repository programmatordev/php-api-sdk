# API

`Api` is the SDK facade. Concrete SDKs extend it and expose resources through purpose-built methods.

Methods not listed here are legacy, internal, or still being reshaped for v3.

## `config(?array $values = null): Config`

Public.

Sets SDK options when an array is provided and always returns the config bag.

```php
$this->config(['timezone' => 'UTC']);

$timezone = $this->config()->get('timezone');
```

SDK users can also read or update options:

```php
$api->config(['timezone' => 'UTC']);
$api->config()->get('timezone');
```

## `resource(string $class): Resource`

Protected helper for creating resource instances from an API class.

```php
final class ExampleApi extends Api
{
    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
```

## `baseUrl(?string $baseUrl): static`

Protected fluent helper for configuring the API base URL.

```php
$this->baseUrl('https://api.example.com');
```

Full request URLs passed to resources override the configured base URL.

## `queryDefaults(array $query): static`

Protected fluent helper for configuring query parameters applied to every request.

```php
$this->queryDefaults(['api_key' => $apiKey, 'locale' => 'en']);
```

Query merge order is:

```text
API defaults < resource options < endpoint-specific options
```

## `headerDefaults(array $headers): static`

Protected fluent helper for configuring headers applied to every request.

```php
$this->headerDefaults(['Accept' => 'application/json']);
```

Header names are not normalized by the package.

## `auth(): AuthBuilder`

Protected access to authentication configuration.

```php
$this->auth()
    ->bearer($token)
    ->query('appid', $apiKey);
```

Authentication is applied automatically to outgoing requests.

See [Authentication](authentication.md) for helper methods, HTTPlug authentication objects, and custom auth callbacks.

## `plugins(): PluginBuilder`

Public access to HTTPlug plugin configuration.

```php
$api->plugins()->add($plugin, priority: 16);
```

Higher priority plugins run earlier. Same-priority plugins are preserved in insertion order.

See [Plugins](plugins.md) for internal plugin order and priority guidance.

## `responses(): ResponseBuilder`

Protected access to response decoding configuration.

```php
$this->responses()->json();
```

When JSON decoding is enabled:

- JSON response bodies are decoded into arrays.
- Empty response bodies become `null`.
- Invalid JSON throws `JsonException`.

When JSON decoding is not enabled, `Response::data()` returns the raw response body string.

This area will grow as response transforms and errors are finalized.

## `errors(): ErrorBuilder`

Protected access to error handling configuration.

By default, HTTP error status codes do not throw. SDK authors opt in to error behavior:

```php
$this->errors()->status(404, NotFoundException::class);
```

Use `statuses()` when an SDK has a common status-to-exception map:

```php
$this->errors()->statuses([
    400 => BadRequestException::class,
    401 => UnauthorizedException::class,
    403 => ForbiddenException::class,
    404 => NotFoundException::class,
    429 => TooManyRequestsException::class,
]);
```

Use a callback when the exception needs response data:

```php
$this->errors()->status(404, function (ErrorContext $context): Throwable {
    return new NotFoundException($context->response()->data()['message']);
});
```

Use `when()` for API-specific error payloads that are not represented by status alone:

```php
$this->errors()->when(function (ErrorContext $context): ?Throwable {
    if (($context->response()->data()['code'] ?? null) !== 'invalid_api_key') {
        return null;
    }

    return new InvalidApiKeyException($context->response()->data()['message']);
});
```

Status callbacks receive `ErrorContext` and must return a `Throwable`. Custom `when()` handlers receive `ErrorContext` and must return a `Throwable` when matched or `null` when not matched.

## `Config`

`Config` stores SDK options.

### `all(): array`

Returns all option values.

```php
$options = $api->config()->all();
```

### `has(string $key): bool`

Checks whether an option exists. A key with a `null` value still exists.

```php
$api->config()->has('timezone');
```

### `get(string $key, mixed $default = null): mixed`

Returns an option value or the default when the key does not exist.

```php
$timezone = $api->config()->get('timezone', 'UTC');
```

### `set(string $key, mixed $value): self`

Sets one option value.

```php
$api->config()->set('timezone', 'UTC');
```

### `merge(array $values): self`

Sets multiple option values.

```php
$api->config()->merge(['timezone' => 'UTC', 'units' => 'metric']);
```
