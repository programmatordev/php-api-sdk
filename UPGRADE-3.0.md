# Upgrade to 3.0

Version 3.0 is a full architecture refresh. This is not a step-by-step migration guide because most SDKs should be reshaped around the new authoring model instead of mechanically replacing old calls.

Use this document as a short summary of what changed and where to look when updating an SDK.

## Resources Use Endpoint Builders

Request helpers now live behind `endpoint()` inside resources.

```php
return $this
    ->endpoint()
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class);
```

Use endpoint modifiers for request-local state:

```php
return $this
    ->endpoint()
    ->query('active', true)
    ->header('X-Tenant', $tenant)
    ->get('/users')
    ->collection(User::class, key: 'data');
```

See [Resource Authoring: Endpoint Requests](docs/04-resource-authoring.md#endpoint-requests), [Resource Authoring: Query And Headers](docs/04-resource-authoring.md#query-and-headers), and [Resources: Endpoint HTTP Methods](docs/05-resources.md#endpoint-http-methods) for details.

## Responses Are First-Class

Entities must implement `EntityInterface`:

```php
public static function fromArray(array $data, ?Context $context = null): static;
```

Use response mapping helpers:

```php
$response->entity(User::class);
$response->collection(User::class, key: 'data');
$response->envelope(UserEnvelope::class);
```

Envelopes must implement `EnvelopeInterface`.

See [Responses: EntityInterface](docs/06-responses.md#entityinterface) and [Responses: EnvelopeInterface](docs/06-responses.md#envelopeinterface) for details.

## Config Replaces Ad Hoc Options

SDK options should use `config()`:

```php
$this->config($options, defaults: [
    'timezone' => 'UTC',
]);
```

The same config is available to entities, envelopes, and hooks through context objects.

See [API](docs/03-api.md) and [Responses: Context](docs/06-responses.md#context) for details.

## Decoding And Errors Are First-Class

Response decoding is configured with `responses()`:

```php
$this->responses()->json();
$this->responses()->xml();
$this->responses()->custom($decoder);
```

HTTP errors do not throw by default. Configure error handling explicitly:

```php
$this->errors()->status(404, NotFoundException::class);
```

See [API](docs/03-api.md) and [Responses](docs/06-responses.md) for details.

## Infrastructure Uses Builders

PSR-18 clients, PSR-17 factories, PSR-6 cache, PSR-3 logging, HTTPlug authentication, plugins, and hooks are still supported. They are configured through grouped builders instead of scattered low-level methods.

```php
$this->auth()->bearer($token);
$this->cache($pool)->defaultTtl(3600);
$this->logger($logger);
$this->plugins()->add($plugin);
$this->hooks()->beforeRequest($hook);
$this->client($client)->requestFactory($requestFactory);
```

`plugins()` remains the right place for transport-level behavior. `hooks()` remains available for request and response lifecycle customization, but response decoding and error handling now have dedicated builders.

Most SDKs only need one authentication helper:

```php
$this->auth()->bearer($token);
```

Use `chain()` only when an API requires multiple authentication rules on the same request.

See [Authentication](docs/07-authentication.md), [HTTP Client](docs/08-http-client.md), [Cache](docs/09-cache.md), [Logging](docs/10-logging.md), [Plugins](docs/11-plugins.md), and [Hooks](docs/12-hooks.md) for details.

## Defaults And Endpoint Overrides

SDK authors can still configure request defaults:

```php
$this->defaultHeaders(['Accept' => 'application/json']);
$this->defaultQueries($this->config()->only('units', 'locale'));
```

SDK authors can configure endpoint-specific cache defaults inside the endpoint chain:

```php
use ProgrammatorDev\Api\Builder\CacheBuilder;

return $this
    ->endpoint()
    ->withCache(fn (CacheBuilder $cache) => $cache->defaultTtl(60))
    ->get('/live')
    ->collection(Event::class, key: 'data');
```

SDK users can override that cache behavior for one resource chain with `withCache()`:

```php
$events = $api
    ->events()
    ->withCache(fn (CacheBuilder $cache) => $cache->defaultTtl(30))
    ->live();
```

Cache precedence is:

```text
API cache config < endpoint cache defaults < resource withCache override
```

The base package provides the generic override mechanism. API-specific fluent helpers, such as `withIncludes()` or `withStatus()`, should live in the concrete SDK.

See [Resource Authoring: API-Specific Resource Chains](docs/04-resource-authoring.md#api-specific-resource-chains), [Resources: Resource Cache Overrides](docs/05-resources.md#resource-cache-overrides), [Cache: Endpoint Defaults](docs/09-cache.md#endpoint-defaults), and [Cache: Resource Overrides](docs/09-cache.md#resource-overrides) for details.

## Setup Is The Escape Hatch

Most SDK-user customization now goes through `setup()`:

```php
$api->setup()->client($client);
$api->setup()->plugins()->add($plugin);
$api->setup()->auth()->bearer($token);
```

SDK authors still configure defaults from the `Api` subclass with protected helpers such as `baseUrl()`, `defaultQueries()`, `auth()`, `responses()`, `errors()`, `cache()`, `logger()`, `plugins()`, and `hooks()`.

See [API](docs/03-api.md) and [Design Approach: Escape Hatch](docs/02-design-approach.md#escape-hatch) for details.

## Send Is Public

`send()` is public as an advanced escape hatch. SDK users can call endpoints that are not modeled by the concrete SDK while still using the SDK's configured base URL, authentication, cache, plugins, hooks, decoding, and error handling.

```php
$response = $api->send('GET', '/unmodeled-endpoint', query: [
    'page' => 1,
]);
```

See [API](docs/03-api.md) for details.

## HTTP Client Discovery

The package uses PHP-HTTP discovery for PSR-18 clients and PSR-17 factories. When the `php-http/discovery` Composer plugin is enabled, missing implementations can be installed automatically from the supported virtual packages.

SDK authors may still require or suggest concrete implementations when they want control over the default HTTP stack.

See [HTTP Client: SDK Author Defaults](docs/08-http-client.md#sdk-author-defaults) and [HTTP Client: SDK User Overrides](docs/08-http-client.md#sdk-user-overrides) for details.

## API-Specific Behavior Belongs In SDKs

API-specific options such as includes, filters, selects, or pagination should be implemented in concrete SDK resources, not in the base package.

```php
$users = $api
    ->users()
    ->withStatus('active')
    ->all();
```

See [Resource Authoring: API-Specific Resource Chains](docs/04-resource-authoring.md#api-specific-resource-chains) for details.

## Test Utilities Are Support Code

The test helpers are intended to support this package and SDK author tests. Concrete SDKs should prefer focused tests around their own resources, entities, envelopes, fake clients, and API-specific fluent helpers.
