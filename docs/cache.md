# Cache

Cache support uses the [PHP-HTTP cache plugin](https://docs.php-http.org/en/latest/plugins/cache.html) with a PSR-6 cache pool.

SDK users can configure cache on an API instance:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$api
    ->cache(new FilesystemAdapter())
    ->defaultTtl(3600);
```

SDK authors can also configure cache from the `Api` class:

```php
$this
    ->cache($pool)
    ->defaultTtl(3600)
    ->methods(['GET', 'HEAD']);
```

## Options

```php
$api->cache($pool)->defaultTtl(3600);
```

Sets the fallback cache TTL in seconds when the response does not provide cache directives. Use `null` to let the cache backend store as long as it can.

```php
$api->cache($pool)->methods(['GET', 'HEAD']);
```

Sets which request methods can be cached.

```php
$api->cache($pool)->responseCacheDirectives(['max-age']);
```

Sets the response cache directives respected by the cache plugin.

## Internal Order

The cache plugin runs at priority `20`, after authentication and before the logger plugin.

When logging is configured, cache hit/miss/write events are logged through the cache plugin listener.
