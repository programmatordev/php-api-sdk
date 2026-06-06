# v3 Architecture Plan

## Goal

`v3.0` should make this package easier and more enjoyable for SDK authors while preserving the power of `v2.x`.

The target experience is fluent and compact:

```php
final class UserResource extends Resource
{
    public function find(int $id): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class);
    }
}
```

SDK users should interact with purpose-built resources and entities, not raw request primitives:

```php
$user = $api->users()->find(1);
```

## Core Concepts

### `Api`

The SDK facade and configuration surface.

Responsibilities:

- Configure base URL.
- Configure authentication.
- Configure global SDK options through a generic config bag.
- Configure global query and header defaults.
- Configure PSR clients and factories.
- Configure cache, logger, plugins, request hooks, response hooks, decoding, and errors.
- Expose resources through a protected/public SDK method pattern.

Non-goals:

- It should not require final SDK users to call raw HTTP request methods.
- It should not accumulate API-specific query concepts like `include`, `select`, `filter`, or pagination.

`Api` should be abstract. It is a base class for concrete SDKs, not something users instantiate directly. It does not need to force subclasses to implement an abstract method in the first phase.

`config()` should always return the config bag. When values or defaults are provided, it merges defaults first and explicit values second:

```php
$this->config(['timezone' => 'UTC'], defaults: ['timezone' => 'Europe/Lisbon']);
$timezone = $this->config()->get('timezone');
```

### `Resource`

The base class for endpoint groups.

Responsibilities:

- Provide protected HTTP helpers like `get`, `post`, `put`, `patch`, and `delete`.
- Hold immutable per-resource request options.
- Allow generic query/header customization through primitives like `query`, `queries`, `header`, and `headers`.
- Return a fresh resource instance by default when created through `Api::resource()`.
- Execute requests immediately when `get`, `post`, `put`, `patch`, or `delete` are called.

Non-goals:

- It should not know about API-specific concepts.
- API-specific packages should add domain vocabulary through their own base resources or traits.
- Resource instance caching is not part of the first v3 slice. This is unrelated to PSR-6 HTTP response caching, which remains a feature parity requirement.

### `RequestOptions`

Immutable request state used by resources.

Responsibilities:

- Store per-resource/per-request query parameters.
- Store per-resource/per-request headers.
- Store body/payload options when needed.
- Merge cleanly with API defaults during request execution.

This avoids cloning and mutating the whole API instance for resource modifiers.

Resource options should be configured fluently before calling the HTTP method:

```php
return $this
    ->query('active', true)
    ->get('/users/{id}', ['id' => $id])
    ->entity(User::class);
```

The path remains an argument of `get`, `post`, `put`, `patch`, and `delete`. Query and header options are configured through fluent resource methods.

Query merge order:

```text
global API defaults < resource options < endpoint-specific options
```

Builder-backed features can follow the same shape when endpoint-specific behavior is useful.

API-level builders configure global defaults:

```php
$api->setup()->cache($pool)->defaultTtl(3600);
```

Request-local overrides should live on the pending request/resource flow instead of mutating the API-level builder:

```php
return $this
    ->get('/weather')
    ->cache(fn (CacheBuilder $cache) => $cache->defaultTtl(300))
    ->entity(CurrentWeather::class);
```

This keeps one SDK instance safe to reuse while still letting SDK authors expose endpoint-specific cache, logger, auth, plugin, or hook behavior where it makes sense. Do this one builder area at a time, starting only when there is a concrete feature need.

Resource constructors may remain public. SDK authors should usually expose resources through `Api::resource()`, but direct construction is useful for testing and advanced use.

### `Response`

Wrapper around decoded data and the raw PSR response.

Responsibilities:

- Expose decoded data.
- Expose raw PSR response when needed.
- Map data to entities.
- Map list data to collections.
- Preserve response metadata when APIs return envelopes.
- Carry response context, including SDK config, for entity and envelope hydration.

Custom envelopes should implement:

```php
interface ResponseEnvelopeInterface
{
    public static function fromResponse(Response $response, ?Context $context = null): static;
}
```

`Response::envelope()` should require `ResponseEnvelopeInterface`.

The wrapper should be named `Response`, not `ApiResponse`. PSR responses are referenced through `ResponseInterface`, so the shorter package name is acceptable and keeps SDK authoring readable.

SDK authors may choose whether endpoint methods return entities directly or response envelope classes:

```php
public function find(int $id): User
{
    return $this->get('/users/{id}', ['id' => $id])->entity(User::class, key: 'data');
}

public function findWithMeta(int $id): UserResponse
{
    return $this->get('/users/{id}', ['id' => $id])->envelope(UserResponse::class);
}
```

### Entity Interface

Required contract for typed response objects used by `Response::entity()` and `Response::collection()`.

Responsibilities:

- Provide a simple convention for hydrating typed objects.
- Keep entities as data/value objects by default.
- Make entity mapping requirements explicit for SDK authors.

Non-goals:

- No lazy loading in the first phase.
- No hidden network calls from entity getters.

Proposed contract:

```php
interface EntityInterface
{
    public static function fromArray(array $data, ?Context $context = null): static;
}
```

`Context` should provide access to SDK config without injecting the full `Api` into entities or response envelopes.

Start with a minimal context API. Add richer access only when implementation needs it.

`toArray()` is not required for v3 entity mapping. It can be added by individual SDK entities or a future optional interface if serialization becomes a real package concern.

## v2 Public Surface Inventory

### `Api`

Current public methods:

- `client`
- `logger`
- `plugins`
- `cache`
- `config`

Original v2 observations:

- The v2 `Api` class is easy to extend but exposes low-level request execution directly.
- SDK packages used `request` from resources.
- Defaults were global to the API instance, which made resource-level fluent options awkward.
- JSON decoding and error handling were commonly implemented through listeners.
- Plugin configuration was automatic inside `request`, which duplicated responsibilities and made request flow harder to reason about.
- Symfony EventDispatcher provided flexibility, but common behavior like JSON decoding and error mapping should not require event listeners in v3.

### Builders

Current public builder classes:

- `ClientBuilder`
- `PluginBuilder`
- `CacheBuilder`
- `LoggerBuilder`

Current capabilities:

- PSR-18 client discovery and injection.
- PSR-17 request and stream factory discovery and injection.
- Plugin registration by priority through `PluginBuilder` / `Api::plugins()`.
- PSR-6 cache configuration.
- PSR-3 logger and formatter configuration.

These capabilities must remain available in v3.

HTTPlug's `PluginClientBuilder` already supports priority ordering and multiple plugins at the same priority. v3 should use that behavior directly or mirror it closely. The current v2 `ClientBuilder` stores plugins as `[priority => plugin]`, which means plugins with the same priority overwrite each other.

### Hooks

Current hook capabilities:

- Mutate request before sending.
- Mutate response after sending.

These capabilities remain through first-class APIs:

- JSON decoding.
- Error mapping.
- Request hooks.
- Response hooks.

Decision: v3 replaces the Symfony EventDispatcher dependency with a smaller request/response pipeline. The pipeline supports request hooks and response hooks, while common features are first-class fluent APIs.

Hooks should receive lightweight context objects rather than long argument lists:

```php
$this->hooks()->beforeRequest(
    fn (RequestContext $context) => $context->request()
);

$this->hooks()->afterResponse(
    fn (ResponseContext $context) => $context->response()
);
```

Request/response contexts should expose the request, response where applicable, and SDK config.

Hooks should use return-object semantics:

```php
$this->hooks()->beforeRequest(
    fn (RequestContext $context) => $context->request()->withHeader('X-Trace-Id', 'abc')
);
```

The returned object replaces the current request/response/data. Returning `null` means no change. This matches PSR-7 immutability and avoids mutable event/context objects.

Initial pipeline order:

```text
create request
beforeRequest hooks
send request
afterResponse hooks
decode body
create Response wrapper
error handling
transform hooks
return Response
```

Error handling should run before transform hooks so API-specific error mapping sees the original decoded API response shape.

### Helpers and Test Utilities

Current helpers:

- `UrlHelper::join`
- `UrlHelper::isAbsoluteUrl`
- `Method` constants.

Current test utilities:

- `AbstractTestCase`
- `MockResponse`
- `TestApi`

The v3 test utilities should focus on helping SDK authors test resources, responses, and entity mapping.

## v3 Replacement Map

| v2 capability | Proposed v3 shape |
| --- | --- |
| Public `Api::request` | Removed; public `Api::send()` delegates HTTP mechanics to internal `Transport` |
| `Api::buildPath` | Path parameter replacement inside `Resource`/transport `get('/x/{id}', ['id' => $id])` |
| `setBaseUrl` / `getBaseUrl` | Fluent `baseUrl(...)`, optional getter only if useful |
| SDK-specific global options | Generic config bag exposed to resources/responses/entities through context |
| Query/header defaults | Fluent `defaultQueries(...)`, `defaultHeaders(...)` |
| Per-resource query options | New `RequestOptions`, exposed through `Resource::query(...)` and SDK-specific traits |
| `setAuthentication` | Fluent `auth()` helper wrapping HTTPlug authentication plus low-level authentication injection |
| Client/factory injection | Keep builder-style or fluent config methods |
| Plugins | Use HTTPlug `PluginClientBuilder`-style priority handling; preserve multiple plugins at the same priority |
| Cache | Keep PSR-6 support, likely through fluent `cache(...)` |
| Logger | Keep PSR-3 support, likely through fluent `logger(...)` |
| `ResponseContentsEvent` for JSON | Removed; first-class `responses()->json()` |
| Post-response listener for errors | First-class status and callback-based error mapping, while preserving hooks |
| Raw response body return | `Response::data()` or `Response::raw()` depending on configuration |
| Manual entity construction in resources | `Response::entity(...)`, `Response::collection(...)`, and `Response::envelope(...)` helpers |

## Decisions

- Resource instances should not be cached by default.
- PSR-6 HTTP response caching remains a separate feature.
- `Api` should be abstract.
- v3 should remove old v2 public low-level methods instead of keeping deprecated aliases.
- SDK-wide options should be stored in a generic config bag.
- SDK config should be available through context objects, not by injecting `Api` into entities.
- `Response::entity()` and `Response::collection()` should require classes that implement `EntityInterface`.
- `Response::envelope()` should support API-specific response envelope classes such as item, collection, metadata, and pagination responses.
- `Response::envelope()` should require a `ResponseEnvelopeInterface` contract with `fromResponse(Response $response, ?Context $context = null)`.
- `Response::collection()` should return a plain array by default.
- Do not add a generic collection object in the first phase. A future `collect()` helper can be considered later if arrays become limiting.
- Symfony EventDispatcher has been replaced with a smaller request/response pipeline.
- v3-native hooks are represented by `HookBuilder`, `RequestContext`, and `ResponseContext`.
- Response body decoding is represented by `ResponseDecoder` and `ResponseFormat`; transport returns raw PSR responses and does not decode. Common formats use `raw()`, `json()`, and `xml()`, while custom response decoding uses `custom()`.
- Method constants are not central to v3 because resources expose `get`, `post`, `put`, `patch`, and `delete` helpers.
- Prefer fluent configuration over public getters.
- Use HTTPlug `PluginClientBuilder` behavior for plugin priority ordering and same-priority plugin preservation.
- Keep `Resource::query()`, `Resource::queries()`, `Resource::header()`, and `Resource::headers()` as generic public primitives.
- Resource modifiers should be immutable and return cloned resources.
- `get`, `post`, `put`, `patch`, and `delete` should execute immediately.
- SDK authors choose whether resource methods return entities directly or custom response envelopes.
- Resource constructors may remain public.
- Use PHPDoc generics where useful, especially for `Api::resource()`, `Response::entity()`, `Response::collection()`, and `Response::envelope()`.
- No reset methods for resource options in the first phase.
- Merge order should be global defaults, then resource options, then endpoint-specific options.
- Client configuration is global API setup only. Do not add `Resource::client()`.
- Defer request-local plugins, cache, hooks, and similar pipeline options until the request-local architecture is clearer. Avoid ad hoc builder cloning or one-off request option shapes. If request-local cache is added later, prefer a smaller cache options object that stores only override values such as default TTL, methods, and cache directives, then merge it with the API-level cache builder during send.
- Header names should not be normalized manually.
- Path parameters should be encoded with `rawurlencode`.
- Query strings should use `http_build_query(..., PHP_QUERY_RFC3986)`.
- Null query values should be omitted by default.
- Boolean query values should use standard `http_build_query` behavior.
- Full URL paths should continue to override the configured base URL.
- Invalid JSON should throw when JSON decoding is enabled.
- Empty JSON response bodies should decode to `null` without throwing.
- Pipeline order should be request hooks, send, response hooks, decode, response wrapper, errors, transforms, return.
- Hooks should return replacement objects/data. Returning `null` means no change.
- Error handling should support both status maps and custom callbacks.
- Error callbacks should receive an `ErrorContext` object and return a `Throwable` when matched or `null` when not matched.
- Fluent auth helpers should wrap existing HTTPlug authentication objects.
- Fluent config should use grouped builders such as `auth()`, `responses()`, `errors()`, `plugins()`, `cache()`, `logger()`, and `hooks()`.
- `auth()` should mirror and wrap HTTPlug authentication behavior rather than inventing new authentication primitives.
- `Response::entity()` and `Response::collection()` should support an optional key for extracting entity data from decoded response envelopes.
- `Response::envelope()` should receive the full decoded `Response`, leaving envelope classes responsible for extracting their data.
- Response data access should stay simple in the first phase. No dot notation or nested key helpers.
- Request body helpers should be friendly for SDK authors while converting to PSR-7 streams internally.
- Resource body helpers should be fluent: `json()`, `form()`, and `body()`.
- Passing an array to `body()` should throw; SDK authors should choose `json()` or `form()` explicitly.
- `json()` should set `Content-Type: application/json`.
- `form()` should set `Content-Type: application/x-www-form-urlencoded`.
- `body(string|StreamInterface)` should not guess `Content-Type`.
- `responses()->json()` should decode all responses, including error responses.
- v3 should not throw for HTTP error status codes by default. SDK authors opt into error behavior through `errors()`.
- Main author-facing classes should stay in the root namespace: `Api`, `Resource`, `Response`, `EntityInterface`, and `ResponseEnvelopeInterface`.
- Internal/supporting classes can live in subnamespaces such as `Request`, `Context`, and `Builder`.
- Package exception classes can be decided as implementation needs emerge.
- Tests should use generic fake SDK fixtures, not downstream SDK names or classes.
- Keep PHP `>=8.1` for now.

## Suggested v3 Authoring API

Example SDK facade:

```php
final class ExampleApi extends Api
{
    public function __construct(string $token)
    {
        parent::__construct();

        $this
            ->baseUrl('https://api.example.com')
            ->auth()->bearer($token)
            ->config(['timezone' => 'UTC'])
            ->defaultQueries(['locale' => 'en'])
            ->responses()->json();
    }

    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
```

Example resource:

```php
final class UserResource extends Resource
{
    public function all(): UserCollection
    {
        return $this
            ->query('active', true)
            ->get('/users')
            ->envelope(UserCollection::class);
    }

    public function find(int $id): User
    {
        return $this
            ->get('/users/{id}', ['id' => $id])
            ->entity(User::class, key: 'data');
    }
}
```

Example custom response envelope:

```php
final class FixtureResource extends Resource
{
    public function find(int $id): FixtureItem
    {
        return $this
            ->get('/v3/football/fixtures/{id}', ['id' => $id])
            ->envelope(FixtureItem::class);
    }
}
```

Example SDK-specific options:

```php
trait IncludeTrait
{
    public function include(string ...$includes): static
    {
        return $this->query('include', implode(';', $includes));
    }
}
```

## Open Questions

No blocking open questions remain for the first implementation phase.

Future-phase questions should be answered when that phase starts, not before:

- Exact hook method names and context details.
- Whether any public configuration getters are useful for testing or advanced extension.
- Whether `Method` remains as a tiny compatibility helper or is removed entirely.
- How endpoint-local cache options should work without muddying request options or cloning full API builders.
- Whether `config()` ever supports nested keys.
- Whether a future `collect()` helper should return a small generic collection object.

## First Implementation Slice

1. Add fake SDK fixtures under tests.
2. Add `Resource`, `RequestOptions`, `Response`, and `EntityInterface`.
3. Add protected/fluent resource creation and request execution to `Api`.
4. Prove one simple endpoint flow with a mock PSR client:

```php
$user = $api->users()->find(1);
```

5. Add tests for path parameter replacement, fluent query options, query merge order, and entity mapping.

Do not add collection mapping, custom envelopes, SDK config, entity context, JSON decoding, errors, hooks, body helpers, auth, plugins, cache, or logger in the first slice. Preserve momentum by getting the authoring experience right first.

## Phase Discipline

Implement v3 incrementally. The plan is intentionally broad because v3 must remain feature complete with v2, but each implementation phase should stay narrow.

1. Prove fluent resource authoring for GET requests and entity mapping.
2. Add collection and custom envelope mapping.
3. Add resource HTTP verb helpers.
4. Add resource body helpers.
5. Add SDK config and entity/response context.
6. Add JSON response decoding and error pipeline.
7. Add auth, plugins, cache, logger, and remaining PSR feature parity.
8. Update README and write `UPGRADE-3.0.md` once names and signatures are stable.

Do not front-load advanced features before the simple SDK authoring path feels right.

## Feature Parity Checklist

Before tagging v3:

- [x] PSR-18 client support.
- [x] PSR-17 request factory support.
- [x] PSR-17 stream factory support.
- [x] PSR-6 cache support.
- [x] PSR-3 logger support.
- [x] Authentication support.
- [x] Plugin support.
- [x] Request hooks.
- [x] Response hooks.
- [x] Response content transformation.
- [x] Query defaults.
- [x] Header defaults.
- [x] Base URL handling.
- [x] Path parameter replacement.
- [x] Resource HTTP verb helpers.
- [x] Resource body helpers.
- [x] JSON response decoding.
- [x] Error mapping.
- [x] Entity mapping.
- [x] Collection mapping.
- [x] Custom response envelope mapping.
- [x] Entity context and SDK config access.
- [x] SDK author test fixtures.
- [ ] README update.
- [ ] `UPGRADE-3.0.md`.
- [ ] Simple API proof.
- [ ] Complex API proof.
