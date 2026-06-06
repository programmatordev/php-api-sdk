<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\Simple\SimpleApi;
use ProgrammatorDev\Api\Test\Fixture\Simple\SimpleEntity;
use ProgrammatorDev\Api\Test\Fixture\Simple\SimpleResponse;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class SimpleApiProofTest extends AbstractTestCase
{
    private const ITEM_RESPONSE = '{
        "id": 1,
        "name": "First item"
    }';

    public function testItemCanBeMappedToEntity(): void
    {
        $client = $this->mockClient(new Response(body: self::ITEM_RESPONSE));

        $api = new SimpleApi('test-key', ['locale' => 'pt', 'version' => 'v2']);
        $api->setup()->client($client);

        $item = $api->items()->find(1);

        $this->assertInstanceOf(SimpleEntity::class, $item);
        $this->assertSame(1, $item->getId());
        $this->assertSame('First item', $item->getName());
        $this->assertSame('pt', $item->getLocale());
        $this->assertSame('v2', $item->getVersion());

        $query = $this->queryFromLastRequest($client);

        $this->assertSame('/items/1', $client->getLastRequest()->getUri()->getPath());
        $this->assertSame('test-key', $query['api_key']);
        $this->assertSame('pt', $query['locale']);
        $this->assertSame('v2', $query['version']);
    }

    public function testItemCanBeMappedToEnvelope(): void
    {
        $client = $this->mockClient(new Response(status: 202, body: self::ITEM_RESPONSE));

        $api = new SimpleApi('test-key');
        $api->setup()->client($client);

        $response = $api->items()->findResponse(1);

        $this->assertInstanceOf(SimpleResponse::class, $response);
        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('en', $response->getLocale());
        $this->assertSame(1, $response->getEntity()->getId());
        $this->assertSame('en', $response->getEntity()->getLocale());
        $this->assertSame('v1', $response->getEntity()->getVersion());
    }

    public function testItemsCanBeMappedToCollection(): void
    {
        $client = $this->mockClient(new Response(body: '{
            "data": [
                {"id": 1, "name": "First item"},
                {"id": 2, "name": "Second item"}
            ]
        }'));

        $api = new SimpleApi('test-key', ['locale' => 'pt']);
        $api->setup()->client($client);

        $items = $api->items()->all();

        $this->assertContainsOnlyInstancesOf(SimpleEntity::class, $items);
        $this->assertSame('First item', $items[0]->getName());
        $this->assertSame('Second item', $items[1]->getName());
        $this->assertSame('pt', $items[0]->getLocale());

        $query = $this->queryFromLastRequest($client);

        $this->assertSame('/items', $client->getLastRequest()->getUri()->getPath());
        $this->assertSame('test-key', $query['api_key']);
    }
}
