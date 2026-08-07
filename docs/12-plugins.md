# Plugins

Plugins are [HTTPlug](https://httplug.io/) middleware applied to outgoing requests.

See the [PHP-HTTP plugin documentation](https://docs.php-http.org/en/latest/plugins/index.html) for the underlying plugin system used here.

HTTP clients and PSR-17 factories are configured through [HTTP Client](09-http-client.md). Plugins are configured separately so middleware order remains explicit.

SDK authors can configure plugins from the `Api` class:

```php
use Http\Client\Common\Plugin;

$this->plugins()->add($plugin, priority: 16);
```

SDK users can also add plugins to a concrete API instance:

```php
$api->setup()->plugins()->add($retryPlugin, priority: 20);
```

Higher priority plugins run earlier. Plugins with the same priority are preserved in insertion order.

## Retry Plugin

HTTPlug plugins can be configured directly. For example, the [retry plugin](https://docs.php-http.org/en/latest/plugins/retry.html) retries failed requests or retryable HTTP responses:

```php
use Http\Client\Common\Plugin\RetryPlugin;

$retryPlugin = new RetryPlugin([
    'retries' => 2,
]);

$this->plugins()->add($retryPlugin, priority: 25);
```

That priority runs retries after authentication is added to the request and before cache or logging.

SDK users can apply the same plugin through `setup()`:

```php
$api->setup()->plugins()->add($retryPlugin, priority: 25);
```

## Internal Plugin Order

The package adds internal plugins with these priorities:

| Priority | Plugin | Description |
| --- | --- | --- |
| `50` | [Content type](https://docs.php-http.org/en/latest/plugins/content-type.html) | Sets a `Content-Type` header when the request body makes it inferable and the header is not already set. |
| `40` | [Content length](https://docs.php-http.org/en/latest/plugins/content-length.html) | Sets request body length metadata before the request is sent. |
| `30` | [Authentication](https://docs.php-http.org/en/latest/plugins/authentication.html) | Applies credentials configured through `auth()`. |
| `20` | [Cache](https://docs.php-http.org/en/latest/plugins/cache.html) | Reads and writes cacheable responses through cache configured with `cache()`. Cache-specific logging is handled through cache listeners when logging is configured. |
| `10` | [Logger](https://docs.php-http.org/en/latest/plugins/logger.html) | Logs HTTP requests and responses through the PSR-3 logger configured with `logger()`. |

Custom plugins use the same priority system, so they can run before, between, or after internal plugins.

```php
$this->plugins()->add($plugin, priority: 60); // before content type
$this->plugins()->add($plugin, priority: 25); // between auth and cache
$this->plugins()->add($plugin, priority: 0);  // after logger
```

## Same Priority

Same-priority plugins do not overwrite each other.

```php
$this->plugins()->add($first, priority: 16);
$this->plugins()->add($second, priority: 16);
```

The request reaches `$first` before `$second`.

## Navigation

- Previous: [Logging](11-logging.md)
- Next: [Hooks](13-hooks.md)
