<?php

namespace ProgrammatorDev\Api\Test\Unit;

use Nyholm\Psr7\Response as PsrResponse;
use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use ProgrammatorDev\Api\Test\Fixture\User;
use ProgrammatorDev\Api\Test\Fixture\UserEnvelope;

class ResponseTest extends AbstractTestCase
{
    public function testDataReturnsDecodedData(): void
    {
        $data = ['id' => 1, 'name' => 'John'];
        $response = new Response($data, new PsrResponse());

        $this->assertSame($data, $response->data());
    }

    public function testRawReturnsPsrResponse(): void
    {
        $rawResponse = new PsrResponse(status: 202);
        $response = new Response(['id' => 1], $rawResponse);

        $this->assertSame($rawResponse, $response->raw());
    }

    public function testEnvelopeReceivesEmptyContextByDefault(): void
    {
        $response = new Response(['data' => ['id' => 1, 'name' => 'John']], new PsrResponse());

        $envelope = $response->envelope(UserEnvelope::class);

        $this->assertNull($envelope->getTimezone());
    }

    public function testEnvelopeReceivesContext(): void
    {
        $config = new Config(['timezone' => 'UTC']);
        $context = new Context($config);
        $response = new Response(['data' => ['id' => 1, 'name' => 'John']], new PsrResponse(), $context);

        $envelope = $response->envelope(UserEnvelope::class);

        $this->assertSame('UTC', $envelope->getTimezone());
    }

    public function testEntityReceivesContext(): void
    {
        $context = new Context(new Config(['timezone' => 'UTC']));
        $response = new Response(['id' => 1, 'name' => 'John'], new PsrResponse(), $context);

        $user = $response->entity(User::class);

        $this->assertSame('UTC', $user->getTimezone());
    }

    public function testCollectionReceivesContext(): void
    {
        $context = new Context(new Config(['timezone' => 'UTC']));
        $response = new Response([
            ['id' => 1, 'name' => 'John'],
        ], new PsrResponse(), $context);

        $users = $response->collection(User::class);

        $this->assertSame('UTC', $users[0]->getTimezone());
    }

    public function testEntityCanBeMappedFromRootData(): void
    {
        $response = new Response(['id' => 1, 'name' => 'John'], new PsrResponse());

        $user = $response->entity(User::class);

        $this->assertSame(1, $user->getId());
        $this->assertSame('John', $user->getName());
    }

    public function testCollectionCanBeMappedFromRootData(): void
    {
        $response = new Response([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ], new PsrResponse());

        $users = $response->collection(User::class);

        $this->assertContainsOnlyInstancesOf(User::class, $users);
        $this->assertSame(1, $users[0]->getId());
        $this->assertSame('John', $users[0]->getName());
        $this->assertSame(2, $users[1]->getId());
        $this->assertSame('Jane', $users[1]->getName());
    }

    public function testCollectionCanMapEmptyData(): void
    {
        $response = new Response([], new PsrResponse());

        $this->assertSame([], $response->collection(User::class));
    }

    public function testCollectionPreservesKeys(): void
    {
        $response = new Response([
            'admin' => ['id' => 1, 'name' => 'John'],
            'user' => ['id' => 2, 'name' => 'Jane'],
        ], new PsrResponse());

        $users = $response->collection(User::class);

        $this->assertSame(['admin', 'user'], array_keys($users));
        $this->assertSame('John', $users['admin']->getName());
        $this->assertSame('Jane', $users['user']->getName());
    }

    public function testEntityRejectsClassThatDoesNotImplementEntity(): void
    {
        $response = new Response(['id' => 1], new PsrResponse());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $response->entity(\stdClass::class);
    }

    public function testCollectionRejectsClassThatDoesNotImplementEntity(): void
    {
        $response = new Response([['id' => 1]], new PsrResponse());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $response->collection(\stdClass::class);
    }

    public function testEnvelopeRejectsClassThatDoesNotImplementResponseEnvelope(): void
    {
        $response = new Response(['data' => ['id' => 1]], new PsrResponse());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $response->envelope(\stdClass::class);
    }

    public function testEntityRejectsNonArrayData(): void
    {
        $response = new Response('not-array', new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Entity data must be an array.');

        $response->entity(User::class);
    }

    public function testEntityRejectsNonArrayDataFromKey(): void
    {
        $response = new Response(['data' => 'not-array'], new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Entity data must be an array.');

        $response->entity(User::class, key: 'data');
    }

    public function testEntityRejectsMissingDataKey(): void
    {
        $response = new Response(['id' => 1], new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Response data key "data" does not exist.');

        $response->entity(User::class, key: 'data');
    }

    public function testCollectionRejectsNonArrayData(): void
    {
        $response = new Response('not-array', new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Collection data must be an array.');

        $response->collection(User::class);
    }

    public function testCollectionRejectsNonArrayDataFromKey(): void
    {
        $response = new Response(['data' => 'not-array'], new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Collection data must be an array.');

        $response->collection(User::class, key: 'data');
    }

    public function testCollectionRejectsMissingDataKey(): void
    {
        $response = new Response(['items' => []], new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Response data key "data" does not exist.');

        $response->collection(User::class, key: 'data');
    }

    public function testCollectionRejectsNonArrayItems(): void
    {
        $response = new Response(['data' => ['not-array-item']], new PsrResponse());

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Collection item data must be an array.');

        $response->collection(User::class, key: 'data');
    }

    public function testEnvelopeCanBeMapped(): void
    {
        $response = new Response(['data' => ['id' => 1, 'name' => 'John']], new PsrResponse(status: 202));

        $envelope = $response->envelope(UserEnvelope::class);

        $this->assertSame(202, $envelope->getStatusCode());
        $this->assertSame(1, $envelope->getUser()->getId());
        $this->assertSame('John', $envelope->getUser()->getName());
        $this->assertNull($envelope->getTimezone());
    }
}
