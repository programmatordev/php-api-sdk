<?php

namespace ProgrammatorDev\Api\Test\Fixture\Weather;

use ProgrammatorDev\Api\Resource;

class WeatherResource extends Resource
{
    public function current(string $city): CurrentWeather
    {
        return $this
            ->get('/weather', query: ['q' => $city])
            ->entity(CurrentWeather::class);
    }

    public function currentResponse(string $city): WeatherResponse
    {
        return $this
            ->get('/weather', query: ['q' => $city])
            ->envelope(WeatherResponse::class);
    }

    /**
     * @return CurrentWeather[]
     */
    public function group(string ...$cities): array
    {
        return $this
            ->get('/group', query: ['q' => implode(',', $cities)])
            ->collection(CurrentWeather::class, key: 'list');
    }
}
