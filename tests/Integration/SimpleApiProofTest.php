<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\Weather\CurrentWeather;
use ProgrammatorDev\Api\Test\Fixture\Weather\WeatherApi;
use ProgrammatorDev\Api\Test\Fixture\Weather\WeatherResponse;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class SimpleApiProofTest extends AbstractTestCase
{
    private const WEATHER_RESPONSE = '{
        "name": "Lisbon",
        "main": {"temp": 21.5},
        "weather": [{"description": "clear sky"}]
    }';

    public function testCurrentWeatherCanBeMappedToEntity(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: self::WEATHER_RESPONSE));

        $api = new WeatherApi('test-key', ['units' => 'imperial', 'lang' => 'pt']);
        $api->setup()->client($client);

        $weather = $api->weather()->current('Lisbon');

        $this->assertInstanceOf(CurrentWeather::class, $weather);
        $this->assertSame('Lisbon', $weather->getCity());
        $this->assertSame(21.5, $weather->getTemperature());
        $this->assertSame('clear sky', $weather->getDescription());
        $this->assertSame('imperial', $weather->getUnits());
        $this->assertSame('pt', $weather->getLang());

        parse_str($client->getLastRequest()->getUri()->getQuery(), $query);

        $this->assertSame('/data/2.5/weather', $client->getLastRequest()->getUri()->getPath());
        $this->assertSame('Lisbon', $query['q']);
        $this->assertSame('test-key', $query['appid']);
        $this->assertSame('imperial', $query['units']);
        $this->assertSame('pt', $query['lang']);
    }

    public function testCurrentWeatherCanBeMappedToEnvelope(): void
    {
        $client = new Client();
        $client->addResponse(new Response(status: 202, body: self::WEATHER_RESPONSE));

        $api = new WeatherApi('test-key');
        $api->setup()->client($client);

        $response = $api->weather()->currentResponse('Lisbon');

        $this->assertInstanceOf(WeatherResponse::class, $response);
        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('metric', $response->getUnits());
        $this->assertSame('Lisbon', $response->getWeather()->getCity());
        $this->assertSame('metric', $response->getWeather()->getUnits());
        $this->assertSame('en', $response->getWeather()->getLang());
    }

    public function testWeatherGroupCanBeMappedToCollection(): void
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{
            "list": [
                {
                    "name": "Lisbon",
                    "main": {"temp": 21.5},
                    "weather": [{"description": "clear sky"}]
                },
                {
                    "name": "Porto",
                    "main": {"temp": 18.0},
                    "weather": [{"description": "few clouds"}]
                }
            ]
        }'));

        $api = new WeatherApi('test-key', ['units' => 'metric']);
        $api->setup()->client($client);

        $weather = $api->weather()->group('Lisbon', 'Porto');

        $this->assertContainsOnlyInstancesOf(CurrentWeather::class, $weather);
        $this->assertSame('Lisbon', $weather[0]->getCity());
        $this->assertSame('Porto', $weather[1]->getCity());
        $this->assertSame('metric', $weather[0]->getUnits());

        parse_str($client->getLastRequest()->getUri()->getQuery(), $query);

        $this->assertSame('/data/2.5/group', $client->getLastRequest()->getUri()->getPath());
        $this->assertSame('Lisbon,Porto', $query['q']);
    }
}
