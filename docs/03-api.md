# API

`Api` is the SDK facade. Concrete SDKs extend it and expose resources through purpose-built methods.

This page documents the public API facade methods available to SDK authors and advanced SDK users.

## Request Execution

### `send()`

```php
send(
    string $method,
    string $path,
    array $pathParams = [],
    array $query = [],
    array $headers = [],
    string|StreamInterface|null $body = null,
): Response
```

Public low-level request helper.

Most SDK methods should use resources and endpoint request helpers. `send()` is useful when an SDK author or advanced SDK user needs to execute a request directly while still using the configured base URL, defaults, auth, plugins, cache, hooks, decoding, and errors.

```php
$response = $api->send('GET', '/users/{id}', ['id' => 1]);
```

Common request inputs can be passed directly:

```php
$response = $api->send(
    method: 'POST',
    path: '/users',
    query: ['active' => true],
    headers: ['Content-Type' => 'application/json'],
    body: '{"name":"John"}'
);
```

> **Backed-enum normalization is available since version 3.1.0.**

The `query` and `headers` arrays accept string- and integer-backed enums. Query
parameters use their backed values, while header values are converted to strings
as required by PSR-7.

Path parameters are encoded and replaced in `{name}` placeholders.

`send()` still runs through the configured SDK pipeline:

- Base URL, default query parameters, and default headers.
- Authentication, plugins, cache, and hooks.
- Response decoding and error mapping.

## SDK Setup

### `config()`

```php
config(array $values = [], array $defaults = []): Config
```

Public.

Merges SDK options when values or defaults are provided and always returns the config bag. Defaults are merged first, so explicit values override them.

```php
$this->config(
    ['timezone' => 'UTC'],
    defaults: ['timezone' => 'Europe/Lisbon']
);

$timezone = $this->config()->get('timezone');
```

SDK users can also read or update options:

```php
$api->config(['timezone' => 'UTC']);
$api->config()->get('timezone');
```

API configuration applies globally. SDK users can override selected values for
one immutable resource chain with `Resource::withConfig()` without changing the
API-wide config. See [Resource-Local Configuration](04-resource-authoring.md#resource-local-configuration).

### `setup()`

```php
setup(): Setup
```

Public access to SDK setup and extension points without adding every setup method to the concrete SDK surface.

```php
$api->setup()->plugins()->add($plugin);
$api->setup()->client($client);
$api->setup()->auth()->bearer($token);
```

SDK authors can call the same setup methods directly from subclasses.

### `resource()`

```php
resource(string $class): Resource
```

Protected helper for creating resource instances from an API class.

See [Resource Authoring](04-resource-authoring.md) for the recommended API-to-resource pattern.

### `resourceWith()`

> **Available since version 3.3.0.**

```php
resourceWith(string $class, mixed ...$arguments): Resource
```

Protected helper for creating a resource with typed SDK-author constructor
dependencies. The resource `Runtime` is provided automatically as the first
constructor argument; additional positional or named arguments are forwarded
after it.

See [Resource Constructor Dependencies](04-resource-authoring.md#resource-constructor-dependencies)
for the complete authoring pattern.

## Request Defaults

### `baseUrl()`

```php
baseUrl(?string $baseUrl): static
```

Protected fluent helper for configuring the API base URL.

```php
$this->baseUrl('https://api.example.com');
```

Full request URLs passed to resources override the configured base URL.

### `defaultQuery()`

```php
defaultQuery(string $name, mixed $value): static
```

Protected fluent helper for configuring one query parameter applied to every request.

```php
$this->defaultQuery('api_key', $apiKey);
```

### `defaultQueries()`

```php
defaultQueries(array $query): static
```

Protected fluent helper for configuring query parameters applied to every request.

```php
$this->defaultQueries(['api_key' => $apiKey, 'locale' => 'en']);
```

Query merge order is:

```text
API defaults < endpoint options
```

### `defaultHeader()`

```php
defaultHeader(string $name, mixed $value): static
```

Protected fluent helper for configuring one header applied to every request.

```php
$this->defaultHeader('Accept', 'application/json');
```

### `defaultHeaders()`

```php
defaultHeaders(array $headers): static
```

Protected fluent helper for configuring headers applied to every request.

```php
$this->defaultHeaders(['Accept' => 'application/json']);
```

Header names are not normalized by the package.

> **Backed-enum normalization is available since version 3.1.0.**

String- and integer-backed enums can be used as default query or header values.
Their scalar values are used when the request is built. Normalization also
applies recursively to nested query values and header value lists. Header values
are converted to strings as required by PSR-7.

## Pipeline Builders

### `auth()`

```php
auth(): AuthBuilder
```

Protected access to authentication configuration.

```php
$this->auth()->bearer($token);
```

Authentication is applied automatically to outgoing requests.

Calling another auth helper replaces the previous authentication. Use `chain()` when multiple authentication rules are required.

See [Authentication](08-authentication.md) for helper methods, HTTPlug authentication objects, and custom auth callbacks.

### `hooks()`

```php
hooks(): HookBuilder
```

Protected access to request and response hooks. SDK users can access hooks through `setup()`.

```php
$this->hooks()->beforeRequest($hook);
$this->hooks()->afterResponse($hook);
```

Hooks are SDK-author extension points. They run around the raw HTTP request and response, before response decoding and error handling.

See [Hooks](13-hooks.md) for hook context objects, return values, and priority behavior.

### `plugins()`

```php
plugins(): PluginBuilder
```

Protected access to HTTPlug plugin configuration. SDK users can access plugins through `setup()`.

```php
$this->plugins()->add($plugin, priority: 16);
```

Higher priority plugins run earlier. Same-priority plugins are preserved in insertion order.

See [Plugins](12-plugins.md) for internal plugin order and priority guidance.

### `cache()`

```php
cache(CacheItemPoolInterface $pool): CacheBuilder
```

Protected access to PSR-6 HTTP response cache configuration. SDK users can access cache through `setup()`.

```php
$this
    ->cache($pool)
    ->defaultTtl(3600)
    ->methods(['GET', 'HEAD']);
```

See [Cache](10-cache.md) for cache options and plugin order.

### `client()`

```php
client(ClientInterface $client): ClientBuilder
```

Protected access to PSR-18 client configuration. SDK users can access client configuration through `setup()`.

```php
$this->client($client);
```

SDK authors can configure PSR-17 factories on the returned builder:

```php
$this
    ->client($client)
    ->requestFactory($requestFactory)
    ->streamFactory($streamFactory);
```

See [HTTP Client](09-http-client.md) for client and factory configuration.

### `logger()`

```php
logger(LoggerInterface $logger): LoggerBuilder
```

Protected access to PSR-3 logger configuration. SDK users can access logging through `setup()`.

```php
$this
    ->logger($logger)
    ->formatter($formatter);
```

See [Logging](11-logging.md) for logger formatting and cache logging.

## Response Handling

### `responses()`

```php
responses(): ResponseBuilder
```

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

### `errors()`

```php
errors(): ErrorBuilder
```

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

## Config Object

`Config` stores SDK options.

### `all()`

```php
all(): array
```

Returns all option values.

```php
$options = $api->config()->all();
```

### `only()`

```php
only(string ...$keys): array
```

Returns selected option values. Missing keys are omitted.

```php
$query = $api->config()->only('units', 'lang');
```

### `has()`

```php
has(string $key): bool
```

Checks whether an option exists. A key with a `null` value still exists.

```php
$api->config()->has('timezone');
```

### `get()`

```php
get(string $key, mixed $default = null): mixed
```

Returns an option value or the default when the key does not exist.

```php
$timezone = $api->config()->get('timezone', 'UTC');
```

### `set()`

```php
set(string $key, mixed $value): self
```

Sets one option value.

```php
$api->config()->set('timezone', 'UTC');
```

### `merge()`

```php
merge(array $values): self
```

Sets multiple option values.

```php
$api->config()->merge(['timezone' => 'UTC', 'units' => 'metric']);
```

## Navigation

- Previous: [Design Approach](02-design-approach.md)
- Next: [Resource Authoring](04-resource-authoring.md)
