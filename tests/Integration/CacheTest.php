<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheTest extends AbstractTestCase
{
    public function testSdkUserCanConfigureCache(): void
    {
        $client = $this->mockClient(new Response(
            headers: ['Cache-Control' => 'max-age=60'],
            body: '{"id":1}'
        ));

        $api = new JsonApi($client);
        $api
            ->setup()->cache(new ArrayAdapter())
            ->defaultTtl(60);

        $first = $api->raw()->fetch();
        $second = $api->raw()->fetch();

        $this->assertSame(['id' => 1], $first->data());
        $this->assertSame(['id' => 1], $second->data());
        $this->assertCount(1, $client->getRequests());
    }
}
