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

```php
$this->auth()->wsse($username, $password);
$this->auth()->wsse($username, $password, hashAlgorithm: 'sha512');
```

Calling another helper replaces the previously configured authentication. Use `chain()` when an SDK needs multiple authentication rules.

## Query Authentication

Use `query()` only when the API requires credentials in the URL.

```php
$this->auth()->query('api_key', $apiKey);
```

For non-sensitive default query parameters such as `locale`, `units`, or `timezone`, use `defaultQueries()` instead:

```php
$this->defaultQueries(['units' => 'metric']);
```

## HTTPlug Authentication Objects

Use `chain()` when an SDK needs to compose specific [HTTPlug authentication implementations](https://docs.php-http.org/en/latest/message/authentication.html):

```php
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\QueryParam;

$this->auth()->chain(
    new Bearer($token),
    new QueryParam(['appid' => $apiKey]),
);
```

This is mostly useful when an SDK author already has an `Http\Message\Authentication` object or needs behavior provided by [`php-http/message`](https://docs.php-http.org/en/latest/message/index.html).

## Conditional Authentication

Use `conditional()` when authentication should only apply to matching requests.

```php
use Http\Message\Authentication\Bearer;
use Http\Message\RequestMatcher\RequestMatcher;

$this->auth()->conditional(
    new RequestMatcher(path: '^/admin'),
    new Bearer($adminToken),
);
```

`conditional()` uses PHP-HTTP's `RequestConditional` authentication internally.

## Custom Authentication

Use `custom()` for request-mutating authentication logic:

```php
use Psr\Http\Message\RequestInterface;

$this->auth()->custom(function (RequestInterface $request): RequestInterface {
    return $request->withHeader('X-Custom-Auth', 'custom');
});
```

The callback receives the outgoing PSR request and must return a PSR request.
Returning anything else throws an `UnexpectedValueException`.
