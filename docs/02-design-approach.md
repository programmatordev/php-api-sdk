# Design Approach

This package is built for two developer audiences:

- SDK authors: developers creating concrete API SDKs with this library.
- SDK users: developers consuming those SDKs in applications.

The goal is to keep SDK authoring fluent and compact, keep SDK usage focused on real API resources, and still expose enough control for developers who need to customize or work around an SDK.

## SDK Authors

SDK authors should usually work inside an `Api` subclass and resources.

```php
final class ExampleApi extends Api
{
    public function __construct(string $apiKey)
    {
        $this->baseUrl('https://api.example.com');
        $this->responses()->json();
        $this->auth()->query('api_key', $apiKey);
    }

    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
```

Resources should make endpoint methods feel direct:

```php
final class UserResource extends Resource
{
    public function find(int $id): User
    {
        return $this
            ->endpoint()
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class, key: 'data');
    }
}
```

## SDK Users

SDK users should mostly see the API that the SDK author created:

```php
$user = $api->users()->find(1);
```

Advanced setup is still available through one explicit entry point:

```php
$api->setup()->client($client);
$api->setup()->plugins()->add($plugin);
$api->setup()->auth()->bearer($token);
```

This keeps the main SDK autocomplete focused while preserving hackability.

## Escape Hatch

If a concrete SDK does not expose an endpoint yet, `send()` can still use the configured SDK pipeline:

```php
$response = $api->send(
    method: 'GET',
    path: '/new-endpoint/{id}',
    pathParams: ['id' => 1],
    query: ['include' => 'details']
);
```

That request still uses configured base URL, defaults, auth, plugins, cache, hooks, response decoding, and error handling.

## Navigation

- Previous: [Getting Started](01-getting-started.md)
- Next: [API](03-api.md)
