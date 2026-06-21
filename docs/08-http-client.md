# HTTP Client

The SDK uses a PSR-18 HTTP client to send requests and PSR-17 factories to create requests and streams.

By default, the package uses PHP-HTTP discovery. When the `php-http/discovery` Composer plugin is enabled, missing PSR-18 and PSR-17 implementations can be installed automatically from the supported virtual packages required by this package.

SDK authors can still provide concrete implementations explicitly when a concrete SDK should control its default HTTP stack.

```php
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

$this
    ->client(Psr18ClientDiscovery::find())
    ->requestFactory(Psr17FactoryDiscovery::findRequestFactory())
    ->streamFactory(Psr17FactoryDiscovery::findStreamFactory());
```

## SDK Author Defaults

SDK authors can configure the client and factories inside the API constructor when discovery should not choose them automatically.

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use ProgrammatorDev\Api\Api;
use Symfony\Component\HttpClient\Psr18Client;

final class ExampleApi extends Api
{
    public function __construct()
    {
        $client = new Psr18Client();
        $psr17Factory = new Psr17Factory();

        $this
            ->client($client)
            ->requestFactory($psr17Factory)
            ->streamFactory($psr17Factory);

        $this->responses()->json();
    }
}
```

## SDK User Overrides

SDK users can replace the client on a concrete API instance.

```php
$api->setup()->client($client);
```

Factories can be replaced through the returned builder.

```php
$api
    ->setup()
    ->client($client)
    ->requestFactory($requestFactory)
    ->streamFactory($streamFactory);
```

## Plugins

HTTPlug plugins are not configured on the client builder. They are configured through `plugins()` so global middleware has one predictable place to live.

```php
$api->setup()->plugins()->add($plugin, priority: 25);
```

See [Plugins](11-plugins.md) for plugin order and priority guidance.

## Navigation

- Previous: [Authentication](07-authentication.md)
- Next: [Cache](09-cache.md)
