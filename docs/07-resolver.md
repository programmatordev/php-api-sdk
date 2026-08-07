# Resolver

> **Available since version 3.2.0.**

The resolver lets entities and envelopes follow API-provided links through the
same configured SDK runtime. SDK authors opt into this behavior explicitly when
a relationship or pagination method calls the resolver.

The package does not inspect entity properties, create proxies, or perform a
request during hydration. A linked request is made only when the SDK method that
uses the resolver is called.

## Access From Context

API runtime responses provide a resolver through hydration context:

```php
$resolver = $context->resolver();
```

Resolver-backed entities and envelopes use the context provided by the API
runtime request.

## Linked Entities

Store the resolver and the relationship URL during hydration, then resolve the
relationship from a purpose-built SDK method:

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;
use ProgrammatorDev\Api\Contract\ResolverInterface;

final class User implements EntityInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $email,
        private readonly ?string $managerUrl,
        private readonly ResolverInterface $resolver,
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            managerUrl: $data['manager']['url'] ?? null,
            resolver: $context->resolver(),
        );
    }

    public function manager(): ?self
    {
        if ($this->managerUrl === null) {
            return null;
        }

        return $this->resolver->entity($this->managerUrl, self::class);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }
}
```

Calling `manager()` performs the linked request the first time that URL is
resolved in the current response graph. Hydrating the original `User` does not.

## Linked Collections

Use `collection()` when a relationship URL returns a list. Given a
`$colleaguesUrl` captured from the payload during `fromArray()`:

```php
/**
 * @return User[]
 */
public function colleagues(): array
{
    return $this->resolver->collection(
        $this->colleaguesUrl,
        User::class,
        key: 'data',
    );
}
```

The resolver returns a plain array and uses the normal entity hydration path for
every item.

## Pagination

Envelopes can use the same resolver for next and previous links:

```php
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;
use ProgrammatorDev\Api\Contract\ResolverInterface;
use ProgrammatorDev\Api\Response\Response;

final class UserPage implements EnvelopeInterface
{
    /**
     * @param User[] $users
     */
    public function __construct(
        private readonly array $users,
        private readonly ?string $nextUrl,
        private readonly ResolverInterface $resolver,
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        $data = $response->data();

        return new self(
            users: $response->collection(User::class, key: 'data'),
            nextUrl: $data['next'] ?? null,
            resolver: $context->resolver(),
        );
    }

    public function next(): ?self
    {
        if ($this->nextUrl === null) {
            return null;
        }

        return $this->resolver->envelope($this->nextUrl, self::class);
    }
}
```

## Resolver Methods

### `get()`

```php
get(string $pathOrUrl): Response
```

Performs a `GET` request and returns the SDK `Response` wrapper.

### `entity()`

```php
entity(string $pathOrUrl, string $class, ?string $key = null): EntityInterface
```

Resolves the URL and maps its response to an entity.

### `collection()`

```php
collection(string $pathOrUrl, string $class, ?string $key = null): array
```

Resolves the URL and maps its response to a plain array of entities.

### `envelope()`

```php
envelope(string $pathOrUrl, string $class): EnvelopeInterface
```

Resolves the URL and maps its response to an envelope.

All resolver methods can propagate request, decoding, error-mapping, and
hydration exceptions.

## Request Pipeline

Resolver requests use the same runtime as the response that provided the
context. This includes:

- Base URL resolution for relative links.
- API default query parameters and headers.
- Authentication, plugins, API-level cache, logging, and hooks.
- Response decoding and error mapping.
- API config and resource-local `withConfig()` values.

Resolver requests start a new request-local pipeline scope. Cache modifiers
applied through an initiating resource or endpoint are not inherited; configure
API-level cache when linked requests should share HTTP cache behavior.

Absolute links are requested as provided and still pass through configured
authentication and plugins. SDK authors should resolve only trusted API links
or use [conditional authentication](08-authentication.md#conditional-authentication)
when credentials must be limited by URL.

## URL Query Precedence

Query values supplied by an API link are authoritative. Missing API defaults are
appended without replacing or reparsing the link query.

With defaults `page=1&locale=en`, resolving:

```text
/users?page=2
```

requests:

```text
/users?page=2&locale=en
```

Repeated values such as `tag=a&tag=b` and keys such as `filter.name` are
preserved.

## Memoization

Each top-level response graph receives its own resolver. Within that graph, the
resolver memoizes the SDK `Response` by the exact path or URL passed to it.
Resolving the same link again avoids another HTTP request, while entity,
collection, and envelope mapping still creates new typed objects.

```php
$user = $api->users()->find(1);

$name = $user->manager()->name();   // Sends the manager request.
$email = $user->manager()->email(); // Reuses the response; no additional request.
```

Each `manager()` call maps a separate `User` object from the memoized response.
The second call does not send another HTTP request. Memoization does not turn
entities into shared mutable objects.

Memoization does not cross independent top-level SDK requests. API-level HTTP
caching can reuse responses across those request graphs.

```php
$firstUser = $api->users()->find(1);
$firstUser->manager(); // Requests the manager URL in the first graph.

$secondUser = $api->users()->find(1);
$secondUser->manager(); // A new graph resolves the manager URL again.
```

With API-level HTTP caching configured, the second graph still uses its own
resolver but the HTTP cache may serve both responses without another network
request.

The initial endpoint response is not registered in resolver memoization. For
example, following `next()` and then a `previous()` link back to the initial page
executes that initial request through the pipeline again. If API-level HTTP
caching is configured and the response is cacheable, the cache can prevent the
request from reaching the network.

```php
$page1 = $api->users()->all(page: 1); // Initial endpoint request.
$page2 = $page1->next();              // Memoized by the resolver.
$page1Again = $page2?->previous();    // Runs page 1 through the pipeline again.
```

## Navigation

- Previous: [Responses](06-responses.md)
- Next: [Authentication](08-authentication.md)
