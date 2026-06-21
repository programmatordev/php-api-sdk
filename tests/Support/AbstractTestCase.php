<?php

namespace ProgrammatorDev\Api\Test\Support;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractTestCase extends TestCase
{
    protected function mockClient(ResponseInterface ...$responses): Client
    {
        $client = new Client();

        foreach ($responses ?: [new Response(body: '{}')] as $response) {
            $client->addResponse($response);
        }

        return $client;
    }

    protected function queryFromLastRequest(Client $client): array
    {
        parse_str($client->getLastRequest()->getUri()->getQuery(), $query);

        return $query;
    }
}
