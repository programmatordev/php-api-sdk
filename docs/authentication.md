# Authentication

Authentication is configured by the SDK author from the `Api` class.

`auth()` returns an `AuthBuilder`. Every configured authentication rule is applied automatically to outgoing requests.

## Common Helpers

Use the built-in helpers for common API authentication styles:

```php
$this->auth()->bearer($token);
```

```php
$this->auth()->basic($username, $password);
```

```php
$this->auth()->header('X-Api-Key', $apiKey);
```

```php
$this->auth()->query('appid', $apiKey);
```

Multiple calls are chained in order:

```php
$this->auth()
    ->bearer($token)
    ->query('appid', $apiKey);
```

Internally, multiple authentication rules become an HTTPlug authentication chain.

## Query Authentication

Use `query()` only when the API requires credentials in the URL.

```php
$this->auth()->query('api_key', $apiKey);
```

For non-sensitive default query parameters such as `locale`, `units`, or `timezone`, use `queryDefaults()` instead:

```php
$this->queryDefaults(['units' => 'metric']);
```

## HTTPlug Authentication Objects

Use `chain()` when an SDK needs to reuse specific HTTPlug authentication implementations:

```php
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\QueryParam;

$this->auth()->chain(
    new Bearer($token),
    new QueryParam(['appid' => $apiKey]),
);
```

This is mostly useful when an SDK author already has an `Http\Message\Authentication` object or needs behavior provided by `php-http/message`.

## Custom Authentication

Use `custom()` for request-mutating authentication logic:

```php
use Psr\Http\Message\RequestInterface;

$this->auth()->custom(function (RequestInterface $request): RequestInterface {
    return $request->withHeader('X-Custom-Auth', 'custom');
});
```

The callback receives the outgoing PSR request and must return a PSR request.
