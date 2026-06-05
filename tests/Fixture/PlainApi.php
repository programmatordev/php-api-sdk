<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Mock\Client;
use ProgrammatorDev\Api\Api;

class PlainApi extends Api
{
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client($client);
        $this->baseUrl('https://api.example.com');
    }

    public function raw(): RawResource
    {
        return $this->resource(RawResource::class);
    }
}
