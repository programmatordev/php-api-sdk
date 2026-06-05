<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Mock\Client;
use ProgrammatorDev\Api\Api;

class FakeApi extends Api
{
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client($client);
        $this->config(['timezone' => 'UTC']);

        $this
            ->baseUrl('https://api.example.com')
            ->queryDefaults(['locale' => 'en'])
            ->responses()
            ->json();
    }

    public function users(): UserResource
    {
        return $this->resource(UserResource::class);
    }
}
