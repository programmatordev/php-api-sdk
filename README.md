# PHP API SDK

[![Latest Version](https://img.shields.io/github/release/programmatordev/php-api-sdk.svg?style=flat-square)](https://github.com/programmatordev/php-api-sdk/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Tests](https://github.com/programmatordev/php-api-sdk/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/programmatordev/php-api-sdk/actions/workflows/ci.yml?query=branch%3Amain)

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
    
    public function getPosts(int $page = 1): string
    {
        // GET https://api.example.com/v1/posts?page=1
        return $this->request(
            method: 'GET',
            path: '/posts',
            query: [
               'page' => $page
            ]
        );
    }
}
```

## Documentation

- [Base URL](#base-url)
- [Requests](#requests)
- [Query defaults](#query-defaults)
- [Header defaults](#header-defaults)
- [Authentication](#authentication)
- [Event listeners](#event-listeners)
- [HTTP client (PSR-18) and HTTP factories (PSR-17)](#http-client-psr-18-and-http-factories-psr-17)
- [Cache (PSR-6)](#cache-psr-6)
- [Logger (PSR-3)](#logger-psr-3)
- [Configure options](#configure-options)

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

The purpose of this method is to have an easy way to build a properly formatted path depending on the inputs or parameters you might have.

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
        // ...
        
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
        // ...
        
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
        // ...
        
        $this->setAuthentication(
            new QueryParam([
                'api_token' => $applicationKey
            ])
        );
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
            // request data is also available
            // $request = $event->getRequest();
            
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

The `addResponseContentsHandler` method is used to manipulate the response that was received from the API.
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

### HTTP Client (PSR-18) and HTTP Factories (PSR-17)

> [!IMPORTANT]  
> The methods in this section are all public.
> The purpose for that is to allow the end user to configure their own HTTP client, HTTP factories and plugins.

- [HTTP client and HTTP factory adapters](#http-client-and-http-factory-adapters)
- [Plugin system](#plugin-system)

#### HTTP Client and HTTP Factory Adapters

By default, this library makes use of the [HTTPlug's Discovery](https://github.com/php-http/discovery) library.
This means that it will automatically find and install a well-known PSR-18 client and PSR-17 factory implementation for you 
(if they were not found on your project):
- [PSR-18 compatible implementations](https://packagist.org/providers/psr/http-client-implementation)
- [PSR-17 compatible implementations](https://packagist.org/providers/psr/http-factory-implementation)

```php
use ProgrammatorDev\Api\Builder\ClientBuilder;

new ClientBuilder(
    // a PSR-18 client
    ?ClientInterface $client = null,
    // a PSR-17 request factory
    ?RequestFactoryInterface $requestFactory = null,
    // a PSR-17 stream factory
    ?StreamFactoryInterface $streamFactory = null
);
```

```php
use ProgrammatorDev\Api\Builder\ClientBuilder;

$this->setClientBuilder(ClientBuilder $clientBuilder): self;
```

```php
use ProgrammatorDev\Api\Builder\ClientBuilder;

$this->getClientBuilder(): ClientBuilder;
```

If you don't want to rely on the discovery of implementations, you can set the ones you want:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use Http\Client\Common\EmulatedHttpAsyncClient
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;

class YourApi extends Api
{
    public function __construct() 
    {
        // ...
        
        $client = new Psr18Client();
        $requestFactory = $streamFactory = new Psr17Factory();
        
        $this->setClientBuilder(
            new ClientBuilder(
                client: $client, 
                requestFactory: $requestFactory, 
                streamFactory: $streamFactory
            )
        );
    }
}
```

The same for the end user:

```php
$api = new YourApi();

$client = new Psr18Client();
$requestFactory = $streamFactory = new Psr17Factory();

$api->setClientBuilder(
    new ClientBuilder(
        client: $client, 
        requestFactory: $requestFactory, 
        streamFactory: $streamFactory
    )
);
```

#### Plugin System

This library enables attaching plugins to the HTTP client. 
A plugin modifies the behavior of the client by intercepting the request and response flow. 

Since plugin order matters, a plugin is added with a priority level, 
and are executed in descending order from highest to lowest.

Check all the [available plugins](https://docs.php-http.org/en/latest/plugins/index.html) or [create your own](https://docs.php-http.org/en/latest/plugins/build-your-own.html).

```php
use Http\Client\Common\Plugin;

$this->getClientBuilder()->addPlugin(Plugin $plugin, int $priority): self;
```

> [!NOTE]
> A `PluginException` will be thrown if there is a plugin with the same `priority` level.

It is important to know that this library already uses various plugins with different priorities.
The following list has all the implemented plugins with the respective priority in descending order (remember that order matters):

| Plugin                                                                                     | Priority | Note                              |
|--------------------------------------------------------------------------------------------|----------|-----------------------------------|
| [`ContentTypePlugin`](https://docs.php-http.org/en/latest/plugins/content-type.html)       | 40       |                                   |
| [`ContentLengthPlugin`](https://docs.php-http.org/en/latest/plugins/content-length.html)   | 32       |                                   |
| [`AuthenticationPlugin`](https://docs.php-http.org/en/latest/plugins/authentication.html)  | 24       | only if authentication is enabled | 
| [`CachePlugin`](https://docs.php-http.org/en/latest/plugins/cache.html)                    | 16       | only if cache is enabled          |
| [`LoggerPlugin`](https://docs.php-http.org/en/latest/plugins/logger.html)                  | 8        | only if logger is enabled         |

For example, if you wanted the client to automatically attempt to re-send a request that failed
(due to unreliable connections and servers, for example)
you can add the [RetryPlugin](https://docs.php-http.org/en/latest/plugins/retry.html);

```php
use ProgrammatorDev\Api\Api;
use Http\Client\Common\Plugin\RetryPlugin;

class YourApi extends Api
{
    public function __construct() 
    {
        // ...
        
        // if a request fails, it will retry at least 3 times
        // priority is 12 to execute the plugin between the cache and logger plugins
        // (check the above plugin order list for more information)
        $this->getClientBuilder()->addPlugin(
            plugin: new RetryPlugin(['retries' => 3])
            priority: 12
        );
    }
}
```

The same for the end user:

```php
$api = new YourApi();

$api->getClientBuilder()->addPlugin(
    plugin: new RetryPlugin(['retries' => 3])
    priority: 12
);
```

### Cache (PSR-6)

> [!IMPORTANT]  
> The methods in this section are all public.
> The purpose for that is to allow the end user to configure their own cache adapter.

This library allows configuring the cache layer of the client for making API requests. 
It uses a standard PSR-6 implementation and provides methods to fine-tune how HTTP caching behaves:
- [PSR-6 compatible implementations](https://packagist.org/providers/psr/cache-implementation)

```php
use ProgrammatorDev\Api\Builder\CacheBuilder;
use Psr\Cache\CacheItemPoolInterface;

new CacheBuilder(
    // a PSR-6 cache adapter
    CacheItemPoolInterface $pool,
    // default lifetime (in seconds) of cache items
    ?int $ttl = 60,
    // An array of HTTP methods for which caching should be applied
    $methods = ['GET', 'HEAD'],
    // An array of cache directives to be compared with the headers of the HTTP response,
    // in order to determine cacheability
    $responseCacheDirectives = ['max-age'] 
);
```

```php
use ProgrammatorDev\Api\Builder\CacheBuilder;

$this->setCacheBuilder(CacheBuilder $cacheBuilder): self;
```

```php
use ProgrammatorDev\Api\Builder\CacheBuilder;

$this->getCacheBuilder(): CacheBuilder;
```

For example, if you wanted to set a file-based cache adapter:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\CacheBuilder;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class YourApi extends Api
{
    public function __construct() 
    {
        // ...
        
        $pool = new FilesystemAdapter();
        
        // file-based cache adapter with a 1-hour default cache lifetime
        $this->setClientBuilder(
            new CacheBuilder(
                pool: $pool, 
                ttl: 3600
            )
        );
    }
    
    public function getPosts(): string
    {
        // you can change the lifetime (and all other parameters)
        // for this specific endpoint
        $this->getCacheBuilder()->setTtl(600);
       
        return $this->request(
            method: 'GET',
            path: '/posts'
        );
    }
}
```

The same for the end user:

```php
$api = new YourApi();

$pool = new FilesystemAdapter();

$api->setCacheBuilder(
    new CacheBuilder(
        pool: $pool, 
        ttl: 3600
    )
);
```

### Logger (PSR-3)

> [!IMPORTANT]  
> The methods in this section are all public.
> The purpose for that is to allow the end user to configure their own logger adapter.

This library allows configuring a logger to save data for making API requests.
It uses a standard PSR-3 implementation and provides methods to fine-tune how logging behaves:
- [PSR-3 compatible implementations](https://packagist.org/providers/psr/log-implementation)

```php
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use Psr\Log\LoggerInterface;
use Http\Message\Formatter;
use Http\Message\Formatter\SimpleFormatter;

new LoggerBuilder(
    // a PSR-3 logger adapter
    LoggerInterface $logger,
     // determines how the log entries will be formatted when they are written by the logger
     // if no formatter is provided, it will default to a SimpleFormatter instance
    ?Formatter $formatter = null
);
```

```php
use ProgrammatorDev\Api\Builder\LoggerBuilder;

$this->setLoggerBuilder(LoggerBuilder $loggerBuilder): self;
```

```php
use ProgrammatorDev\Api\Builder\LoggerBuilder;

$this->getLoggerBuilder(): LoggerBuilder;
```

As an example, if you wanted to save logs into a file:

```php
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class YourApi extends Api
{
    public function __construct() 
    {
        // ...
        
        $logger = new Logger('api');
        $logger->pushHandler(new StreamHandler('/logs/api.log'));
        
        $this->setLoggerBuilder(
            new LoggerBuilder(
                logger: $logger
            )
        );
    }
}
```

The same for the end user:

```php
$api = new YourApi();

$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('/logs/api.log'));

$api->setLoggerBuilder(
    new LoggerBuilder(
        logger: $logger
    )
);
```

### Configure Options

It is very common for APIs to offer different options (like language, timezone, etc.).
To simplify the process of configuring options, the [`OptionsResolver`](https://symfony.com/doc/current/components/options_resolver.html) is available.
It allows you to create a set of default options and their constraints such as required options, default values, allowed types, etc. 
It then resolves the given options `array` against these default options to ensure it meets all the constraints.

For example, if an API has a language and timezone options:

```php
use ProgrammatorDev\Api\Api;

class YourApi extends Api
{
    private array $options = [];

    public function __construct(array $options = []) 
    {
        parent::__construct();
        
        $this->configureOptions($options);
        $this->configureApi();
    }
    
    private function configureOptions(array $options): void
    {
        // set defaults values, if none were provided
        $this->optionsResolver->setDefault('timezone', 'UTC');
        $this->optionsResolver->setDefault('language', 'en');

        // set allowed types
        $this->optionsResolver->setAllowedTypes('timezone', 'string');
        $this->optionsResolver->setAllowedTypes('language', 'string');

        // set allowed values
        $this->optionsResolver->setAllowedValues('timezone', \DateTimeZone::listIdentifiers());
        $this->optionsResolver->setAllowedValues('language', ['en', 'pt']);
        
        // resolve and set to options property
        $this->options = $this->optionsResolver->resolve($options);
    }
    
    private function configureApi(): void
    {
        // set required base url
        $this->setBaseUrl('https://api.example.com/v1');
        
        // set options as query defaults (will be included in all requests)
        $this->addQueryDefault('language', $this->options['language']);
        $this->addQueryDefault('timezone', $this->options['timezone']);
    }
    
    public function getPosts(int $page = 1): string
    {
        // GET https://api.example.com/v1/posts?language=en&timezone=UTC&page=1
        return $this->request(
            method: 'GET',
            path: '/posts',
            query: [
                'page' => $page
            ]
        );
    }
}
```

For the end user, it should look like this:

```php
$api = new YourApi([
    'language' => 'pt'
]);

// GET https://api.example.com/v1/posts?language=pt&timezone=UTC&page=1
$posts = $api->getPosts();
```

For all available methods, check the official page documentation [here](https://symfony.com/doc/current/components/options_resolver.html).

## Contributing

Any form of contribution to improve this library (including requests) will be welcome and appreciated.
Make sure to open a pull request or issue.

## License

This project is licensed under the MIT license.
Please see the [LICENSE](LICENSE) file distributed with this source code for further information regarding copyright and licensing.