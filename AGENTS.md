# AGENTS.md

## Project Purpose

`programmatordev/php-api-sdk` is a lightweight foundation for building fluent,
maintainable PHP API SDKs. It should make common SDK work compact and enjoyable
without hiding the request lifecycle or becoming a heavy framework.

The package serves two developer audiences:

- SDK authors extend the package to build concrete API SDKs.
- SDK users consume those concrete SDKs.

Favor the SDK-author experience when choosing internal extension points and
authoring APIs. Keep the SDK-user surface focused on real resources and endpoint
methods, with deliberate escape hatches for advanced use cases.

## Core Concepts

Keep the architecture centered on a small set of clear responsibilities:

- `Api`: the SDK facade, resource entry point, and author-owned configuration
  surface.
- `Setup`: the explicit SDK-user setup and hackability surface exposed through
  `Api::setup()`.
- `Runtime`: the internal configured runtime used by resources for configuration
  access and request execution.
- `Resource`: an immutable endpoint group and the primary SDK-author workflow.
- `Endpoint`: an immutable builder for request-local query, header, and body
  options.
- `RequestOptions`: the request-local query, header, and body state carried by an
  endpoint.
- `Response`: the decoded/raw response wrapper and mapping surface.
- `Entity`: the optional contract for typed response data objects.

Do not blur these responsibilities without a concrete simplification. In
particular, keep setup concerns out of resources and API-specific behavior out of
the generic runtime.

## Authoring Experience

Keep the common resource path compact:

```php
return $this
    ->endpoint()
    ->get('/path/{id}', ['id' => $id])
    ->entity(User::class);
```

Prefer fluent SDK authoring over low-level request construction inside
resources. Advanced PSR capabilities should remain available without dominating
the basic workflow.

Expose advanced SDK-user setup through one obvious surface:

```php
$api->setup()->plugins()->add($plugin);
$api->setup()->client($client);
$api->setup()->auth()->bearer($token);
```

Keep SDK-author setup helpers protected on `Api` by default. This preserves a
focused SDK-user autocomplete surface while allowing concrete SDKs to provide
purpose-built public configuration methods.

`send()` may remain public as an advanced escape hatch for endpoints not modeled
by a concrete SDK. It must still use the configured authentication, plugins,
cache, hooks, decoding, and error handling.

## Design Principles

- Keep the package small, explicit, and composable. Add an abstraction only when
  it removes real complexity, improves SDK authoring, or supports an established
  capability.
- Hackability is a feature. SDK users may intentionally customize the runtime
  through `setup()`; that power should be explicit rather than hidden.
- Builders are mutable configuration objects. Fluent builder methods configure
  state, while methods returning stored or built data use `get*()` names.
- Resources and endpoints are immutable request scopes. Fluent modifiers must
  return clones and must not leak state into the API, sibling resources, or
  later requests.
- Scoped overrides should retain access to the current API runtime and merge
  lazily. Do not snapshot shared configuration or runtime services when only one
  value needs to be overridden.
- One effective configuration must flow through request creation, hooks,
  transport, errors, responses, and hydration. Different stages must not observe
  different values for the same request.
- Independent modifiers should compose in any order unless ordering is an
  intentional part of their contract. Applying one modifier must not discard
  another modifier's state.
- Normalize ergonomic author-facing values at the narrowest shared boundary.
  For example, normalize supported request values after defaults and endpoint
  options merge, before serialization.
- Authentication strategies must be explicit. Multiple strategies compose
  through `auth()->chain(...)` rather than relying on implicit precedence.
- Keep entities as response data/value objects by default. Do not introduce
  hidden network calls, lazy loading, or transparent proxy behavior.
- Keep API-specific vocabulary in concrete SDK packages. Concepts such as
  includes, selects, filters, and pagination should build on generic resource
  primitives rather than enter the base package without broad applicability.
- Avoid architecture that requires constant dependency injection or repetitive
  boilerplate in downstream SDKs.

## Compatibility And Capabilities

Preserve backward compatibility by default. Do not remove, rename, or change the
meaning of public APIs or protected SDK-author extension points without explicit
approval for a breaking release.

When extending behavior:

- Prefer additive APIs and compatible normalization at existing boundaries.
- Preserve established defaults and merge precedence.
- Keep original objects unchanged when introducing scoped fluent behavior.
- Call out any unavoidable break explicitly before implementation.
- Document a migration path for every approved breaking change.

Maintain support for the package's core capabilities:

- PSR-18 HTTP clients.
- PSR-17 request and stream factories.
- PSR-6 caches.
- PSR-3 loggers.
- Authentication.
- Plugins and middleware.
- Request and response hooks.
- Query and header defaults.
- Base URL and path construction.
- Response decoding and transformation.
- Error handling.
- Test utilities for SDK authors where they provide clear value.

## Implementation Approach

- Read adjacent code and tests before editing. Follow existing naming, fluent
  patterns, typing, and file organization.
- Prefer the smallest coherent change that solves the current problem.
- Reuse existing helpers and extension points before creating new layers.
- Keep internal and public APIs consistent in terminology and return behavior.
- Add comments only for non-obvious constraints, ordering, isolation, or design
  decisions. Do not narrate self-explanatory code.
- Treat request construction, execution, and response mapping as one pipeline.
  Changes at one stage must be checked for effects on the others.

## Documentation

Update documentation alongside every user-visible or SDK-author-visible behavior
change.

Documentation should explain:

- The core concepts and their responsibilities.
- How to create and configure a simple SDK.
- How to author resources and request options.
- How to map responses to entities, collections, and envelopes.
- How to configure authentication, clients, factories, cache, logging, plugins,
  hooks, and errors.
- How to create API-specific fluent helpers on top of generic primitives.
- Availability versions for newly introduced features when relevant.

Use focused documents as topics grow. Prefer clear navigation and concise,
complete examples over a single large guide. Keep examples API-neutral unless a
real downstream SDK is being used as an integration proof.

For breaking releases, provide an upgrade guide that identifies each changed
contract and its replacement path. Do not create version-specific upgrade guides
for additive minor releases.

## Testing

Add or update tests with every meaningful behavior change. Test public behavior
and supported extension points rather than private implementation details.

Use fixtures, fake APIs, test resources, mock clients, and local response objects
to represent realistic SDK authoring and usage. Cover both:

- Base package behavior.
- SDK-author behavior through small concrete SDK fixtures.

For scoped or pipeline behavior, verify isolation and propagation explicitly:

- Original and sibling instances remain unchanged.
- Defaults and local overrides merge with documented precedence.
- Later runtime configuration remains visible where it is not overridden.
- Hooks, errors, responses, and hydration observe the same effective context.
- Cache behavior does not leak request-local state.
- Independent fluent modifiers compose correctly.

## Downstream Validation

Validate important design decisions against representative downstream SDKs,
including both simple integrations and complex integrations with resources,
envelopes, metadata, pagination, filtering, and many entities.

The base package should make both styles straightforward without becoming
coupled to either API. A downstream friction point is evidence to evaluate, not
automatic justification for adding API-specific behavior to the core.
