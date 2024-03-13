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
- Event listeners;
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

## Documentation

Methods available only to developers of the API SDK, for configuration and validation of data.
End users have no access to these methods.

### Base URL

Getter and setter for the base URL. 
Base URL is the common part of the API URL and will be used in all requests.

```php
$this->setBaseUrl(string $baseUrl): self
```

```php
$this->getBaseUrl(): string
```

### Requests

- [`request`](#request)
- [`buildPath`](#buildpath)

#### `request`

This method is used to send a request to an API.

```php
use Psr\Http\Message\StreamInterface;

$this->request(
    string $method, 
    string $path, 
    array $query [], 
    array $headers = [], 
    StreamInterface|string $body = null
): mixed
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
check the [`addResponseContentsHandler`](#addresponsecontentshandler) method in the [Event Listeners](#event-listeners) section.

#### `buildPath`

The purpose of this method is to have an easy way to build a properly formatted path URL depending on the inputs or parameters you might have.

```php
$this->buildPath(string $path, array $parameters): string;
```

For example, if you want to build a path that has a dynamic id:

```php
use ProgrammatorDev\Api\Api;

class YourApi extends Api
{
    public function __construct() 
    {
        parent::__construct();
        
        $this->setBaseUrl('https://api.example.com/v1');
    }
    
    public function getPostComments(int $postId): string
    {
        // GET https://api.example.com/v1/posts/1/comments
        return $this->request(
            method: 'GET',
            path: $this->buildPath('/posts/{postId}/comments', [
                'postId' => $postId
            ])
        );
    }
}
```

### Query Defaults

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

For example, if you want to add a language query parameter in all requests:

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

### Header Defaults

These methods are used for handling default headers.
Default headers are applied to every API request.

```php
$this->addHeaderDefault(string $name, mixed $value): self
```

```php
$this->getHeaderDefault(string $name): mixed
```

```php
$this->removeHeaderDefault(string $name): self
```

For example, if you want to add a language header value in all requests:

```php
use ProgrammatorDev\Api\Api;

class YourApi extends Api
{
    public function __construct(string $language = 'en') 
    {
        parent::__construct();
        
        $this->setBaseUrl('https://api.example.com/v1');
        $this->addHeaderDefault('X-LANGUAGE', $language);
    }
    
    public function getPosts(): string
    {
        // GET https://api.example.com/v1/posts with an 'X-LANGUAGE' => 'en' header value
        return $this->request(
            method: 'GET',
            path: '/posts'
        );
    }
    
    public function getCategories(): string
    {
        // a header with the same name, passed in the request method, will overwrite a header default
        // GET https://api.example.com/v1/categories with an 'X-LANGUAGE' => 'pt' header value
        return $this->request(
            method: 'GET',
            path: '/categories',
            headers: [
                'X-LANGUAGE' => 'pt'
            ]
        );
    }
}
```

> [!NOTE]
> A header with the same name, passed in the `request` method, will overwrite a header default.
> Check the `getCategories` method in the example above.

### Authentication

Getter and setter for API authentication. 
Uses the [authentication component](https://docs.php-http.org/en/latest/message/authentication.html) from [PHP HTTP](https://docs.php-http.org/en/latest/index.html).

```php
use Http\Message\Authentication;

$this->setAuthentication(?Authentication $authentication): self;
```

```php
use Http\Message\Authentication;

$this->getAuthentication(): ?Authentication;
```

Available authentication methods:
- [`BasicAuth`](https://docs.php-http.org/en/latest/message/authentication.html#id1) Username and password
- [`Bearer`](https://docs.php-http.org/en/latest/message/authentication.html#bearer) Token
- [`Wsse`](https://docs.php-http.org/en/latest/message/authentication.html#id2) Username and password
- [`QueryParam`](https://docs.php-http.org/en/latest/message/authentication.html#query-params) Array of query parameter values
- [`Header`](https://docs.php-http.org/en/latest/message/authentication.html#header) Header name and value
- [`Chain`](https://docs.php-http.org/en/latest/message/authentication.html#chain) Array of authentication instances
- `RequestConditional` A request matcher and authentication instances

You can also [implement your own](https://docs.php-http.org/en/latest/message/authentication.html#implement-your-own) authentication method.

For example, if you have an API that is authenticated with a query parameter:

```php
use ProgrammatorDev\Api\Api;
use Http\Message\Authentication\QueryParam;

class YourApi extends Api
{
    public function __construct(string $applicationKey) 
    {
        parent::__construct();
        
        $this->setBaseUrl('https://api.example.com/v1');
        $this->setAuthentication(new QueryParam(['api_token' => $applicationKey]));
    }
    
    public function getPosts(): string
    {
        // GET https://api.example.com/v1/posts?api_token=cd982h3diwh98dd23d32j
        return $this->request(
            method: 'GET',
            path: '/posts'
        );
    }
}
```

### Event Listeners

- [`addPostRequestHandler`](#addpostrequesthandler)
- [`addResponseContentsHandler`](#addresponsecontentshandler)

#### `addPostRequestHandler`

The `addPostRequestHandler` method is used to add a handler function that is executed after a request has been made. 
This handler function can be used to inspect the request and response data that was sent to, and received from, the API.
This event listener will be applied to every API request.

```php
$this->addPostRequestHandler(callable $handler, int $priority = 0): self;
```

For example, you can use this event listener to handle API errors:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Event\PostRequestEvent;

class YourApi extends Api
{
    public function __construct() 
    {
        // ...
        
        // a PostRequestEvent is passed as an argument
        $this->addPostRequestHandler(function(PostRequestEvent $event) {
            // $event->getRequest() is also available
            $response = $event->getResponse();
            $statusCode = $response->getStatusCode();
            
            // if there was a response with an error status code
            if ($statusCode >= 400) {
                // throw an exception
                match ($statusCode) {
                    400 => throw new BadRequestException(),
                    404 => throw new NotFoundException(),
                    default => throw new UnexpectedErrorException()
                };
            }
        });
    }
    
    // ...
}
```

#### `addResponseContentsHandler`

On the other hand, the `addResponseContentsHandler` method is used to manipulate the response that was received from the API.
This event listener will be applied to every API request.

```php
$this->addResponseContentsHandler(callable $handler, int $priority = 0): self;
```

For example, if the API responses are JSON strings, you can use this event listener to decode them into arrays:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Event\ResponseContentsEvent;

class YourApi extends Api
{
    public function __construct() 
    {
        // ...
        
        // a ResponseContentsEvent is passed as an argument
        $this->addResponseContentsHandler(function(ResponseContentsEvent $event) {
            // get response contents and decode json string into an array
            $contents = $event->getContents();
            $contents = json_decode($contents, true);
            
            // set handled contents
            $event->setContents($contents);
        });
    }
    
    public function getPosts(): array
    {
        // will return an array
        return $this->request(
            method: 'GET',
            path: '/posts'
        );
    }
}
```

## Contributing

Any form of contribution to improve this library (including requests) will be welcome and appreciated.
Make sure to open a pull request or issue.

## License

This project is licensed under the MIT license.
Please see the [LICENSE](LICENSE) file distributed with this source code for further information regarding copyright and licensing.