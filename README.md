# PHP API SDK

[![Latest Version](https://img.shields.io/github/release/programmatordev/php-api-sdk.svg?style=flat-square)](https://github.com/programmatordev/sportmonksfootball-php-api/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Tests](https://github.com/programmatordev/php-api-sdk/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/programmatordev/sportmonksfootball-php-api/actions/workflows/ci.yml?query=branch%3Amain)

A library for creating SDKs in PHP with support for:
- [PSR-18 HTTP clients](https://www.php-fig.org/psr/psr-18);
- [PSR-17 HTTP factories](https://www.php-fig.org/psr/psr-17);
- [PSR-6 caches](https://www.php-fig.org/psr/psr-6);
- [PSR-3 logs](https://www.php-fig.org/psr/psr-3);
- Authentication;
- Events;
- ...and more.

## Requirements

- PHP 8.1 or higher.

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require programmatordev/php-api-sdk
```

To use the library, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once 'vendor/autoload.php';
```

## Basic Usage

Just extend your API library with the `Api` class and have fun coding:

```php
use ProgrammatorDev\Api\Api;

class YourApi extends Api
{
    public function __construct() 
    {
        parent::__construct();
        
        // minimum required config
        $this->setBaseUrl('https://api.example.com/v1');
    }
    
    public function getRecords(int $page = 1): string
    {
        return $this->request(
            method: 'GET',
            path: '/records',
            query: [
               'page' => $page
            ]
        );
    }
}
```

## Protected Methods

Methods available only to developers of the API SDK, for configuration and validation of data.
End users have no access to these methods.

### Base URL

Getter and setter for the base URL. 
Base URL is the common part of the API URL and will be used in all requests.

```php
$this->getBaseUrl(): string
```

```php
$this->setBaseUrl(string $baseUrl): self
```

For example, if you have an endpoint https://api.example.com/v1/users, then https://api.example.com/v1 is the base URL:

```php
$this->setBaseUrl('https://api.example.com/v1');
$baseUrl = $this->getBaseUrl(); // returns "https://api.exampel.com/v1";
```

### Requests

This method is used to send a request to an API. 
It takes an HTTP `method` (like GET or POST), a `path` (the API endpoint), an array of `query` parameters, 
an array of `headers`, and a `body` as arguments.

```php
$this->request(string $method, string $path, array $query [], array $headers = [], StreamInterface|string $body = null): mixed
```

> [!NOTE]
> A `ConfigException` will be thrown if a base URL is not set (this is, if it is empty). 
> Check the [`setBaseUrl`](#base-url) method for more information.

> [!NOTE]
> A `ClientException` will be thrown if there is an error while processing the request.

For example, if you wanted to get a list of users with pagination:

```php
use ProgrammatorDev\Api\Api;

class YourApi extends Api
{
    public function __construct() 
    {
        parent::__construct();
        
        // minimum required config
        $this->setBaseUrl('https://api.example.com/v1');
    }
    
    public function getUsers(int $page = 1, int $perPage = 20): string
    {
        // GET https://api.example.com/v1/users?page=1&limit=20
        return $this->request(
            method: 'GET',
            path: '/users',
            query: [
                'page' => $page,
                'limit' => $perPage
            ]
        );
    }
}
```

By default, this method will return a `string` as it will be the response of the request as is.
If you want to change how the response is handled in all requests (for example, decode a JSON string into an array), 
check the [`addResponseContentsHandler`]() method in the [Events]() section.

## Query Defaults

These methods are used for handling default query parameters. 
Default query parameters are applied to every API request.

```php
$this->addQueryDefault(string $name, mixed $value): self
```

```php
$this->getQueryDefault(string $name): mixed
```

```php
$this->removeQueryDefault(string $name): self
```

For example, if you want to add a language parameter on all requests:

```php
use ProgrammatorDev\Api\Api;

class YourApi extends Api
{
    public function __construct(string $language = 'en') 
    {
        parent::__construct();
        
        $this->setBaseUrl('https://api.example.com/v1');
        $this->addQueryDefault('lang', $language);
    }
    
    public function getPosts(): string
    {
        // GET https://api.example.com/v1/posts?lang=en
        return $this->request(
            method: 'GET',
            path: '/posts'
        );
    }
    
    public function getCategories(): string
    {
        // a query parameter with the same name, passed in the request method, will overwrite a query default
        // GET https://api.example.com/v1/categories?lang=pt
        return $this->request(
            method: 'GET',
            path: '/categories',
            query: [
                'lang' => 'pt'
            ]
        );
    }
}
```

> [!NOTE]
> A `query` parameter with the same name, passed in the `request` method, will overwrite a query default.
> Check the `getCategories` method in the example above.

## Contributing

Any form of contribution to improve this library (including requests) will be welcome and appreciated.
Make sure to open a pull request or issue.

## License

This project is licensed under the MIT license.
Please see the [LICENSE](LICENSE) file distributed with this source code for further information regarding copyright and licensing.