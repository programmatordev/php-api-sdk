<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Mock\Client;
use ProgrammatorDev\Api\Api;
use Psr\Http\Message\ResponseInterface;

class PlainApi extends Api
{
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->client($client);
        $this->baseUrl('https://api.example.com');
    }

    /**
     * @param callable(ResponseInterface): mixed $decoder
     */
    public function decodeWith(callable $decoder): self
    {
        $this->responses()->custom($decoder);

        return $this;
    }

    public function raw(): RawResource
    {
        return $this->resource(RawResource::class);
    }
}
