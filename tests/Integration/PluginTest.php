<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Client\Common\Plugin;
use Http\Mock\Client;
use Http\Promise\Promise;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Http\Message\RequestInterface;

class PluginTest extends AbstractTestCase
{
    public function testConfiguredPluginsAreAppliedByPriorityOrder(): void
    {
        $client = $this->client(responses: 1);

        (new JsonApi($client))
            ->usePlugin($this->headerPlugin('low'), priority: 8)
            ->usePlugin($this->headerPlugin('high'), priority: 40)
            ->usePlugin($this->headerPlugin('middle'), priority: 16)
            ->raw()
            ->fetch();

        $this->assertSame(['high', 'middle', 'low'], $client->getLastRequest()->getHeader('X-Plugin-Order'));
    }

    public function testSdkUserCanConfigurePlugins(): void
    {
        $client = $this->client(responses: 1);
        $api = new JsonApi($client);

        $api->setup()->plugins()->add($this->headerPlugin('user'), priority: 20);
        $api->raw()->fetch();

        $this->assertSame(['user'], $client->getLastRequest()->getHeader('X-Plugin-Order'));
    }

    public function testConfiguredPluginsWithSamePriorityAreAppliedInInsertionOrder(): void
    {
        $client = $this->client(responses: 1);

        (new JsonApi($client))
            ->usePlugin($this->headerPlugin('first'), priority: 16)
            ->usePlugin($this->headerPlugin('second'), priority: 16)
            ->raw()
            ->fetch();

        $this->assertSame(['first', 'second'], $client->getLastRequest()->getHeader('X-Plugin-Order'));
    }

    public function testPluginPriorityCanTargetInternalAuthOrder(): void
    {
        $client = $this->client(responses: 1);

        (new JsonApi($client))
            ->useBearerAuth('secret')
            ->usePlugin($this->authStatePlugin('before-auth'), priority: 35)
            ->usePlugin($this->authStatePlugin('after-auth'), priority: 25)
            ->raw()
            ->fetch();

        $this->assertSame(
            ['before-auth:missing', 'after-auth:present'],
            $client->getLastRequest()->getHeader('X-Auth-State')
        );
    }

    public function testConfiguredPluginsAreNotDuplicatedAcrossRequests(): void
    {
        $client = $this->client(responses: 2);
        $api = (new JsonApi($client))->usePlugin($this->headerPlugin('once'), priority: 16);

        $api->raw()->fetch();
        $api->raw()->fetch();

        $this->assertSame(['once'], $client->getRequests()[0]->getHeader('X-Plugin-Order'));
        $this->assertSame(['once'], $client->getRequests()[1]->getHeader('X-Plugin-Order'));
    }

    private function headerPlugin(string $value): Plugin
    {
        return new class($value) implements Plugin {
            public function __construct(private readonly string $value) {}

            public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
            {
                return $next($request->withAddedHeader('X-Plugin-Order', $this->value));
            }
        };
    }

    private function authStatePlugin(string $label): Plugin
    {
        return new class($label) implements Plugin {
            public function __construct(private readonly string $label) {}

            public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
            {
                $state = $request->hasHeader('Authorization') ? 'present' : 'missing';

                return $next($request->withAddedHeader('X-Auth-State', sprintf('%s:%s', $this->label, $state)));
            }
        };
    }

    private function client(int $responses): Client
    {
        $client = new Client();

        for ($i = 0; $i < $responses; $i++) {
            $client->addResponse(new Response(body: '{}'));
        }

        return $client;
    }
}
