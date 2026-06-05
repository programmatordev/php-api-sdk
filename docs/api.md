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
