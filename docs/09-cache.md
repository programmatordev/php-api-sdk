# Cache

Cache support uses the [PHP-HTTP cache plugin](https://docs.php-http.org/en/latest/plugins/cache.html) with a PSR-6 cache pool.

SDK authors can configure cache from the `Api` class:

```php
$this
    ->cache($pool)
    ->defaultTtl(3600)
    ->methods(['GET', 'HEAD']);
```

SDK users can also configure cache through `setup()`:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$api->setup()->cache(new FilesystemAdapter())->defaultTtl(3600);
```

When no TTL is configured, cached responses use a default TTL of 3600 seconds.

## Options

```php
$this->cache($pool)->defaultTtl(3600);
```

Sets the fallback cache TTL in seconds when the response does not provide cache directives. Use `null` to let the cache backend store as long as it can.

```php
$this->cache($pool)->methods(['GET', 'HEAD']);
```

Sets which request methods can be cached.

```php
$this->cache($pool)->responseCacheDirectives(['max-age']);
```

Sets the response cache directives respected by the cache plugin.

## Cache Layers

Cache has three layers:

```text
API cache config < endpoint cache defaults < resource withCache override
```

The API cache config is required because it provides the PSR-6 pool. Endpoint defaults and resource overrides only adjust an already configured cache builder.

```php
$api->setup()->cache($pool);
```

Use each layer for a different job:

- API cache config: global cache pool and fallback defaults.
- Endpoint cache defaults: SDK-author intent for a specific endpoint.
- Resource `withCache()` override: SDK-user override for one resource chain.

## Endpoint Defaults

SDK authors can set endpoint-specific cache defaults on the endpoint builder:

```php
public function live(): FixtureCollection
{
    return $this
        ->endpoint()
        ->withCache(fn (CacheBuilder $cache) => $cache->defaultTtl(60))
        ->get('/fixtures/live')
        ->envelope(FixtureCollection::class);
}
```

This is useful when the SDK author knows that one endpoint should behave differently from the global default. For example, realtime endpoints may default to a short TTL while stable lookup endpoints may default to a longer TTL.

Endpoint defaults do not mutate the API cache builder and do not affect later requests.

`Endpoint::cache()` is deprecated since version 3.2.0. Use `Endpoint::withCache()` instead.

## Resource Overrides

SDK users can override cache behavior for one resource chain with `withCache()`. The override wins over endpoint defaults, does not mutate the API cache builder, and does not affect later resource instances.

```php
$fixtures = $api
    ->fixtures()
    ->withCache(fn (CacheBuilder $cache) => $cache->defaultTtl(30))
    ->live();
```

`withCache()` is intentionally on the resource chain instead of every endpoint method argument. That keeps endpoint signatures focused on API-specific parameters while still giving SDK users a clear override path.

## Missing Global Cache

Endpoint defaults and resource overrides require global cache configuration:

```php
$api->setup()->cache($pool);
```

If an endpoint or resource override is used without global cache configuration, the request fails because there is no PSR-6 pool to clone and adjust.

## Internal Order

The cache plugin runs at priority `20`, after authentication and before the logger plugin.

When logging is configured, cache hit/miss/write events are logged through the cache plugin listener.

See [Logging](10-logging.md) for cache log output.

## Navigation

- Previous: [HTTP Client](08-http-client.md)
- Next: [Logging](10-logging.md)
