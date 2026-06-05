# Plugins

Plugins are [HTTPlug](https://httplug.io/) middleware applied to outgoing requests.

See the [PHP-HTTP plugin documentation](https://docs.php-http.org/en/latest/plugins/index.html) for the underlying plugin system used here.

SDK authors can configure plugins from the `Api` class:

```php
use Http\Client\Common\Plugin;

$this->plugins()->add($plugin, priority: 16);
```

SDK users can also add plugins to a concrete API instance:

```php
$api->plugins()->add($retryPlugin, priority: 20);
```

Resources can add plugins for one request:

```php
return $this
    ->plugins(fn (PluginBuilder $plugins) => $plugins->add($plugin, priority: 25))
    ->get('/users');
```

Higher priority plugins run earlier. Plugins with the same priority are preserved in insertion order. For the same priority, API-level plugins run before request-local resource plugins.

## Internal Plugin Order

The package adds internal plugins with these priorities:

| Priority | Plugin |
| --- | --- |
| `50` | Content type |
| `40` | Content length |
| `30` | Authentication |
| `20` | Cache |
| `10` | Logger |

## What Internal Plugins Do

- [Content type](https://docs.php-http.org/en/latest/plugins/content-type.html): sets a `Content-Type` header when the request body makes it inferable and the header is not already set.
- [Content length](https://docs.php-http.org/en/latest/plugins/content-length.html): sets request body length metadata before the request is sent.
- [Authentication](https://docs.php-http.org/en/latest/plugins/authentication.html): applies credentials configured through `auth()`.
- [Cache](https://docs.php-http.org/en/latest/plugins/cache.html): reads and writes cacheable responses through PSR-6 cache support. Cache-specific logging is handled through cache listeners when logging is configured.
- [Logger](https://docs.php-http.org/en/latest/plugins/logger.html): logs HTTP requests and responses through the configured PSR-3 logger.

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

## Request-Local Plugins

`Resource::plugins()` stores plugin configuration in request options. It does not mutate the API-level plugin builder.

Merge order is:

```text
internal plugins < API plugins < request-local resource plugins
```
