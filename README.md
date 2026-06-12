# PHP API SDK

[![Latest Version](https://img.shields.io/github/release/programmatordev/php-api-sdk.svg?style=flat-square)](https://github.com/programmatordev/php-api-sdk/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Tests](https://github.com/programmatordev/php-api-sdk/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/programmatordev/php-api-sdk/actions/workflows/ci.yml?query=branch%3Amain)

These docs describe how to create API SDKs with this package.

This package is built for two developer audiences:

- SDK authors: developers creating concrete API SDKs with this library.
- SDK users: developers consuming those SDKs in applications.

The goal is to keep SDK authoring fluent and compact, keep SDK usage focused on real API resources, and still expose enough control for developers who need to customize or work around an SDK.

The practical guides below show how to build resources, map responses, and configure the HTTP pipeline. Read [Design Approach](docs/02-design-approach.md) for more about the reasoning behind the API shape.

## Requirements

- PHP `>=8.1`
- PSR-18 HTTP client support
- PSR-17 request and stream factory support

The package uses PHP-HTTP discovery for PSR-18 clients and PSR-17 factories. When the `php-http/discovery` Composer plugin is enabled, missing implementations can be installed automatically from the supported virtual packages.

## Installation

```bash
composer require programmatordev/php-api-sdk
```

SDK packages may still require or suggest concrete PSR-18 and PSR-17 implementations when they want tighter control over the default HTTP stack.

## Guides

- [Getting Started](docs/01-getting-started.md): create a small SDK with an API facade, resource, entity, and response mapping.
- [Design Approach](docs/02-design-approach.md): the reasoning behind fluent SDK authoring, clean SDK usage, and hackability.
- [API](docs/03-api.md): SDK facade setup methods, configuration, and extension points.
- [Resource Authoring](docs/04-resource-authoring.md): deeper guide for resource methods, query/header options, request bodies, entity mapping, collections, envelopes, and API-specific resource chains.
- [Resources](docs/05-resources.md): resource classes and endpoint request helpers.
- [Responses](docs/06-responses.md): decoded data, raw responses, entities, collections, envelopes, and context.
- [Authentication](docs/07-authentication.md): configure bearer, basic, header, query, HTTPlug, and custom authentication.
- [HTTP Client](docs/08-http-client.md): configure PSR-18 clients and PSR-17 factories.
- [Cache](docs/09-cache.md): configure PSR-6 HTTP response caching.
- [Logging](docs/10-logging.md): configure PSR-3 logging and HTTP/cache log output.
- [Plugins](docs/11-plugins.md): configure HTTPlug middleware and priority ordering.
- [Hooks](docs/12-hooks.md): run SDK-author callbacks around requests and responses.
- [API Reference](docs/13-api-reference.md): authoring methods and contracts.
