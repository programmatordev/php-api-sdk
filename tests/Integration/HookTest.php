<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Context\RequestContext;
use ProgrammatorDev\Api\Context\ResponseContext;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use UnexpectedValueException;

class HookTest extends AbstractTestCase
{
    public function testBeforeRequestHookCanReplaceRequest(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":false}'));

        (new JsonApi($client))
            ->beforeRequest(fn (RequestContext $context) => $context->request()->withHeader('X-Hook', 'before'))
            ->raw()
            ->fetch();

        $this->assertSame('before', $client->getLastRequest()->getHeaderLine('X-Hook'));
    }

    public function testAfterResponseHookCanReplaceResponse(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":false}'));

        $response = (new JsonApi($client))
            ->afterResponse(fn (ResponseContext $context) => new Response(status: 202, body: '{"ok":true}'))
            ->raw()
            ->fetch();

        $this->assertSame(202, $response->raw()->getStatusCode());
        $this->assertSame(['ok' => true], $response->data());
    }

    public function testHookReturningNullLeavesObjectUnchanged(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":false}'));

        $response = (new JsonApi($client))
            ->beforeRequest(fn (RequestContext $context) => null)
            ->afterResponse(fn (ResponseContext $context) => null)
            ->raw()
            ->fetch();

        $this->assertFalse($client->getLastRequest()->hasHeader('X-Hook'));
        $this->assertSame(200, $response->raw()->getStatusCode());
        $this->assertSame(['ok' => false], $response->data());
    }

    public function testHooksRunByPriorityAndInsertionOrder(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":false}'));

        (new JsonApi($client))
            ->beforeRequest(fn (RequestContext $context) => $context->request()->withAddedHeader('X-Hook-Order', 'low'), priority: 10)
            ->beforeRequest(fn (RequestContext $context) => $context->request()->withAddedHeader('X-Hook-Order', 'first'), priority: 20)
            ->beforeRequest(fn (RequestContext $context) => $context->request()->withAddedHeader('X-Hook-Order', 'second'), priority: 20)
            ->raw()
            ->fetch();

        $this->assertSame(['first', 'second', 'low'], $client->getLastRequest()->getHeader('X-Hook-Order'));
    }

    public function testHooksCanReadSdkConfig(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":false}'));

        $api = new JsonApi($client);
        $api->config(['tenant' => 'acme']);

        $api
            ->beforeRequest(fn (RequestContext $context) => $context->request()->withHeader('X-Tenant', $context->apiContext()->config()->get('tenant')))
            ->raw()
            ->fetch();

        $this->assertSame('acme', $client->getLastRequest()->getHeaderLine('X-Tenant'));
    }

    public function testHooksReceiveResourceConfigOverrides(): void
    {
        $client = $this->mockClient(new Response(body: '{"ok":true}'));
        $seen = [];

        $api = new JsonApi($client);
        $api->beforeRequest(
            function (RequestContext $context) use (&$seen) {
                $config = $context->apiContext()->config();
                $seen['before'] = [
                    'tenant' => $config->get('tenant'),
                    'region' => $config->get('region'),
                ];

                return $context->request()->withHeader('X-Tenant', $seen['before']['tenant']);
            }
        );
        $api->afterResponse(
            function (ResponseContext $context) use (&$seen): void {
                $config = $context->apiContext()->config();
                $seen['after'] = [
                    'tenant' => $config->get('tenant'),
                    'region' => $config->get('region'),
                ];
            }
        );

        $api
            ->raw()
            ->withConfig(['tenant' => 'acme'])
            ->withConfig(['region' => 'eu'])
            ->fetch();

        $this->assertSame('acme', $client->getLastRequest()->getHeaderLine('X-Tenant'));
        $this->assertSame([
            'before' => ['tenant' => 'acme', 'region' => 'eu'],
            'after' => ['tenant' => 'acme', 'region' => 'eu'],
        ], $seen);
        $this->assertFalse($api->config()->has('tenant'));
        $this->assertFalse($api->config()->has('region'));
    }

    public function testBeforeRequestHookRejectsInvalidReturnValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Before request hooks must return a RequestInterface instance or null.');

        (new JsonApi($this->mockClient(new Response(body: '{"ok":false}'))))
            ->beforeRequest(fn (RequestContext $context) => 'invalid')
            ->raw()
            ->fetch();
    }

    public function testAfterResponseHookRejectsInvalidReturnValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('After response hooks must return a ResponseInterface instance or null.');

        (new JsonApi($this->mockClient(new Response(body: '{"ok":false}'))))
            ->afterResponse(fn (ResponseContext $context) => 'invalid')
            ->raw()
            ->fetch();
    }
}
