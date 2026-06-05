# Plugins

Plugins are HTTPlug middleware applied to outgoing requests.

SDK authors can configure plugins from the `Api` class:

```php
use Http\Client\Common\Plugin;

$this->plugins()->add($plugin, priority: 16);
```

SDK users can also add plugins to a concrete API instance:

```php
$api->plugins()->add($retryPlugin, priority: 20);
```

Higher priority plugins run earlier. Plugins with the same priority are preserved in insertion order.

## Internal Plugin Order

The package adds internal plugins with these priorities:

| Priority | Plugin |
| --- | --- |
| `40` | Content type |
| `32` | Content length |
| `24` | Authentication |
| `16` | Cache |
| `8` | Logger |

Custom plugins use the same priority system, so they can run before, between, or after internal plugins.

```php
$this->plugins()->add($plugin, priority: 48); // before content type
$this->plugins()->add($plugin, priority: 20); // between auth and cache
$this->plugins()->add($plugin, priority: 0);  // after logger
```

## Same Priority

Same-priority plugins do not overwrite each other.

```php
$this->plugins()->add($first, priority: 16);
$this->plugins()->add($second, priority: 16);
```

The request reaches `$first` before `$second`.
