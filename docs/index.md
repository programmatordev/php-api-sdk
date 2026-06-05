# Documentation

These docs describe how to create API SDKs with this package.

These docs describe the upcoming `v3.0` API. The stable `v2.x` docs remain in the root `README.md` until v3 is released.

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
- [Resource Authoring](resource-authoring.md): deeper guide for resource methods, query/header options, request bodies, entity mapping, collections, envelopes, and API-specific traits.
- [API Reference](api-reference.md): current v3 authoring methods and contracts.
