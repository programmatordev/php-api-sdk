<?php

namespace ProgrammatorDev\Api\Test\Fixture\Weather;

use ProgrammatorDev\Api\Api;

class WeatherApi extends Api
{
    public function __construct(string $apiKey, array $options = [])
    {
        parent::__construct();

        $this->config($options, defaults: [
            'units' => 'metric',
            'lang' => 'en',
        ]);

        $this->baseUrl('https://api.openweathermap.org/data/2.5');
        $this->auth()->query('appid', $apiKey);
        $this->defaultQueries($this->config()->only('units', 'lang'));
        $this->responses()->json();
    }

    public function weather(): WeatherResource
    {
        return $this->resource(WeatherResource::class);
    }
}
