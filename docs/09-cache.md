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

## Resource And Endpoint Overrides

SDK users can override cache behavior for one resource chain with `withCache()`:

```php
$fixtures = $api
    ->fixtures()
    ->withCache(fn (CacheBuilder $cache) => $cache->defaultTtl(30))
    ->live();
```

SDK authors can set endpoint-specific cache defaults on the endpoint builder:

```php
public function live(): FixtureCollection
{
    return $this
        ->endpoint()
        ->cache(fn (CacheBuilder $cache) => $cache->defaultTtl(60))
        ->get('/fixtures/live')
        ->envelope(FixtureCollection::class);
}
```

Cache overrides require API-level cache configuration, because the global cache configuration provides the PSR-6 pool:

```php
$api->setup()->cache($pool);
```

Cache configuration is merged in this order:

```text
API cache config < endpoint cache defaults < resource withCache override
```

## Internal Order

The cache plugin runs at priority `20`, after authentication and before the logger plugin.

When logging is configured, cache hit/miss/write events are logged through the cache plugin listener.

See [Logging](10-logging.md) for cache log output.

## Navigation

- Previous: [HTTP Client](08-http-client.md)
- Next: [Logging](10-logging.md)
