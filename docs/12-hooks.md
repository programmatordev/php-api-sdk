# Hooks

Hooks let SDK authors and SDK users run callbacks around the HTTP request without exposing low-level request execution.

SDK authors can configure hooks from an `Api` subclass:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;

final class ExampleApi extends Api
{
    public function __construct(string $apiKey)
    {
        $this->baseUrl('https://api.example.com');
        $this->responses()->json();

        $this->hooks()->beforeRequest(
            fn (RequestContext $context) => $context
                ->request()
                ->withHeader('X-Api-Key', $apiKey)
        );

        $this->hooks()->afterResponse(
            fn (ResponseContext $context) => $context
                ->response()
                ->withoutHeader('X-Debug-Trace')
        );
    }
}
```

## Before Request

`beforeRequest()` runs after the PSR-7 request is created and before it is sent.

```php
$this->hooks()->beforeRequest(function (RequestContext $context) {
    return $context->request()->withHeader('X-Tenant', $context->apiContext()->config()->get('tenant'));
});
```

Return a `RequestInterface` to replace the request. Return `null` to leave it unchanged. Any other return value throws.

## After Response

`afterResponse()` runs after the HTTP response is received and before response decoding, response wrapping, and error handling.

```php
$this->hooks()->afterResponse(function (ResponseContext $context) {
    return $context->response()->withoutHeader('X-Debug-Trace');
});
```

Return a `ResponseInterface` to replace the response. Return `null` to leave it unchanged. Any other return value throws.

## Priority

Higher priority hooks run earlier. Hooks with the same priority run in insertion order.

```php
$this->hooks()->beforeRequest($first, priority: 20);
$this->hooks()->beforeRequest($second, priority: 20);
$this->hooks()->beforeRequest($later, priority: 10);
```

The request reaches `$first`, then `$second`, then `$later`.

## Context

`RequestContext` exposes:

- `request()`
- `apiContext()`

`ResponseContext` exposes:

- `request()`
- `response()`
- `apiContext()`

The shared `Context` gives hooks access to SDK config without injecting the full API instance.

## Order

The current v3 request flow is:

```text
create request
beforeRequest hooks
send request
afterResponse hooks
decode response
create Response
errors
return Response
```

## Navigation

- Previous: [Plugins](11-plugins.md)
- Next: [API Reference](13-api-reference.md)
