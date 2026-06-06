# Documentation

These docs describe how to create API SDKs with this package.

This package is built for two developer audiences:

- SDK authors: developers creating concrete API SDKs with this library.
- SDK users: developers consuming those SDKs in applications.

The goal is to keep SDK authoring fluent and compact, keep SDK usage focused on real API resources, and still expose enough control for developers who need to customize or work around an SDK.

The practical guides below show how to build resources, map responses, and configure the HTTP pipeline. Read [Design Approach](design-approach.md) for more about the reasoning behind the API shape.

## Requirements

- PHP `>=8.1`
- A PSR-18 HTTP client implementation
- PSR-17 request and stream factory implementations

The package can discover compatible HTTP clients and factories through PHP-HTTP discovery when implementations are installed.

## Installation

```bash
composer require programmatordev/php-api-sdk
```

SDK packages should also require or suggest concrete PSR-18 and PSR-17 implementations suitable for their users.

## Guides

- [Getting Started](getting-started.md): create a small SDK with an API facade, resource, entity, and response mapping.
- [Authentication](authentication.md): configure bearer, basic, header, query, HTTPlug, and custom authentication.
- [HTTP Client](http-client.md): configure PSR-18 clients and PSR-17 factories.
- [Cache](cache.md): configure PSR-6 HTTP response caching.
- [Logging](logging.md): configure PSR-3 logging and HTTP/cache log output.
- [Plugins](plugins.md): configure HTTPlug middleware and priority ordering.
- [Hooks](hooks.md): run SDK-author callbacks around requests and responses.
- [Resource Authoring](resource-authoring.md): deeper guide for resource methods, query/header options, request bodies, entity mapping, collections, envelopes, and API-specific traits.
- [API Reference](api-reference.md): current v3 authoring methods and contracts.
- [Design Approach](design-approach.md): the reasoning behind fluent SDK authoring, clean SDK usage, and hackability.
