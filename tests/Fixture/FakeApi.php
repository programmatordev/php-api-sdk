<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Mock\Client;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;

class FakeApi extends Api
{
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->setClientBuilder(new ClientBuilder($client));
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
