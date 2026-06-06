# HTTP Client

The SDK uses a PSR-18 HTTP client to send requests and PSR-17 factories to create requests and streams.

If compatible implementations are installed, the package can discover them automatically through PHP-HTTP discovery. SDK users can also provide concrete implementations explicitly.

```php
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

$api
    ->client(Psr18ClientDiscovery::find())
    ->requestFactory(Psr17FactoryDiscovery::findRequestFactory())
    ->streamFactory(Psr17FactoryDiscovery::findStreamFactory());
```

## SDK Author Defaults

SDK authors can configure the client and factories inside the API constructor when the SDK should control its defaults.

```php
use Programmatordev\ApiSdk\Api;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ExampleApi extends Api
{
    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $apiKey,
    ) {
        $this
            ->baseUrl('https://api.example.com')
            ->defaultQueries(['api_key' => $apiKey])
            ->client($client)
            ->requestFactory($requestFactory)
            ->streamFactory($streamFactory)
            ->responses()
            ->json();
    }
}
```

## SDK User Overrides

SDK users can replace the client on a concrete API instance.

```php
$api->client($client);
```

Factories can be replaced through the returned builder.

```php
$api
    ->client($client)
    ->requestFactory($requestFactory)
    ->streamFactory($streamFactory);
```

## Plugins

HTTPlug plugins are not configured on the client builder. They are configured through `plugins()` so global middleware has one predictable place to live.

```php
$api->plugins()->add($plugin, priority: 25);
```

See [Plugins](plugins.md) for plugin order and priority guidance.

