# API

`Api` is the SDK facade. Concrete SDKs extend it and expose resources through purpose-built methods.

Methods not listed here are legacy, internal, or still being reshaped for v3.

## `send(string $method, string $path, array $pathParams = [], ?RequestOptions $options = null): Response`

Public low-level request helper.

Most SDK methods should use resources and the protected resource verb helpers. `send()` is useful when an SDK author or advanced SDK user needs to execute a request directly while still using the configured base URL, defaults, auth, plugins, cache, hooks, decoding, and errors.

```php
$response = $api->send('GET', '/users/{id}', ['id' => 1]);
```

Path parameters are encoded and replaced in `{name}` placeholders.

`send()` still runs through the configured SDK pipeline:

- Base URL, default query parameters, and default headers.
- Authentication, plugins, cache, and hooks.
- Response decoding and error mapping.

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

## `setup(): ApiSetup`

Public access to SDK setup and extension points without adding every setup method to the concrete SDK surface.

```php
$api->setup()->plugins()->add($plugin);
$api->setup()->client($client);
$api->setup()->auth()->bearer($token);
```

SDK authors can call the same setup methods directly from subclasses.

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

## `defaultQuery(string $name, mixed $value): static`

Protected fluent helper for configuring one query parameter applied to every request.

```php
$this->defaultQuery('api_key', $apiKey);
```

## `defaultQueries(array $query): static`

Protected fluent helper for configuring query parameters applied to every request.

```php
$this->defaultQueries(['api_key' => $apiKey, 'locale' => 'en']);
```

Query merge order is:

```text
API defaults < resource options < endpoint-specific options
```

## `defaultHeader(string $name, mixed $value): static`

Protected fluent helper for configuring one header applied to every request.

```php
$this->defaultHeader('Accept', 'application/json');
```

## `defaultHeaders(array $headers): static`

Protected fluent helper for configuring headers applied to every request.

```php
$this->defaultHeaders(['Accept' => 'application/json']);
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

## `hooks(): HookBuilder`

Protected access to request and response hooks. SDK users can access hooks through `setup()`.

```php
$this->hooks()->beforeRequest($hook);
$this->hooks()->afterResponse($hook);

$api->setup()->hooks()->beforeRequest($hook);
```

Hooks are SDK-author extension points. They run around the raw HTTP request and response, before response decoding and error handling.

See [Hooks](hooks.md) for hook context objects, return values, and priority behavior.

## `plugins(): PluginBuilder`

Protected access to HTTPlug plugin configuration. SDK users can access plugins through `setup()`.

```php
$this->plugins()->add($plugin, priority: 16);

$api->setup()->plugins()->add($plugin, priority: 16);
```

Higher priority plugins run earlier. Same-priority plugins are preserved in insertion order.

See [Plugins](plugins.md) for internal plugin order and priority guidance.

## `cache(CacheItemPoolInterface $pool): CacheBuilder`

Protected access to PSR-6 HTTP response cache configuration. SDK users can access cache through `setup()`.

```php
$this
    ->cache($pool)
    ->defaultTtl(3600)
    ->methods(['GET', 'HEAD']);

$api->setup()->cache($pool)->defaultTtl(3600);
```

See [Cache](cache.md) for cache options and plugin order.

## `client(ClientInterface $client): ClientBuilder`

Protected access to PSR-18 client configuration. SDK users can access client configuration through `setup()`.

```php
$this->client($client);

$api->setup()->client($client);
```

SDK authors can configure PSR-17 factories on the returned builder:

```php
$this
    ->client($client)
    ->requestFactory($requestFactory)
    ->streamFactory($streamFactory);
```

See [HTTP Client](http-client.md) for client and factory configuration.

## `logger(LoggerInterface $logger): LoggerBuilder`

Protected access to PSR-3 logger configuration. SDK users can access logging through `setup()`.

```php
$this
    ->logger($logger)
    ->formatter($formatter);

$api->setup()->logger($logger);
```

See [Logging](logging.md) for logger formatting and cache logging.

## `responses(): ResponseBuilder`

Protected access to response decoding configuration.

```php
$this->responses()->json();
$this->responses()->xml();
$this->responses()->custom($decoder);
```

Available response formats:

- `raw()`: response bodies are returned as strings.
- `json()`: response bodies are decoded into arrays; empty bodies become `null`; invalid JSON throws `JsonException`.
- `xml()`: response bodies are decoded into `SimpleXMLElement`; empty bodies become `null`; invalid XML throws `RuntimeException`.
- `custom()`: receives the raw PSR response and returns the value used as `Response::data()`.

When no format is configured, `raw()` is used.

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
