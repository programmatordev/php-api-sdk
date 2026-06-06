<?php

namespace ProgrammatorDev\Api\Test\Fixture\Simple;

use ProgrammatorDev\Api\Api;

class SimpleApi extends Api
{
    public function __construct(string $apiKey, array $options = [])
    {
        parent::__construct();

        $this->config($options, defaults: [
            'locale' => 'en',
            'version' => 'v1',
        ]);

        $this->baseUrl('https://api.example.com');
        $this->auth()->query('api_key', $apiKey);
        $this->defaultQueries($this->config()->only('locale', 'version'));
        $this->responses()->json();
    }

    public function items(): SimpleResource
    {
        return $this->resource(SimpleResource::class);
    }
}
