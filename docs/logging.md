# Logging

Logging uses the [PHP-HTTP logger plugin](https://docs.php-http.org/en/latest/plugins/logger.html) with a PSR-3 logger.

SDK authors can configure logging from the `Api` class:

```php
$this->logger($logger);
```

SDK users can also configure logging through `setup()`:

```php
$api->setup()->logger($logger);
```

## Formatter

The logger plugin can receive a custom formatter.

```php
$this
    ->logger($logger)
    ->formatter($formatter);
```

The formatter is passed directly to the HTTPlug logger plugin.

## Cache Logging

When cache and logging are both configured, cache activity is also logged through the cache plugin listener.

```php
$this
    ->cache($pool)
    ->defaultTtl(3600);

$this->logger($logger);
```

The cache listener logs:

- `HTTP cache hit:` when a cached response is reused.
- `HTTP response cached:` when a response is stored.

The log context includes the cache key and, when available, the cache expiration date.

## Internal Order

The logger plugin runs at priority `10`, after cache.

That means the cache plugin can serve cached responses before the request reaches later plugins. Cache-specific logging is handled by the cache listener instead of relying only on the logger plugin.

See [Plugins](plugins.md) for the full internal plugin order.
