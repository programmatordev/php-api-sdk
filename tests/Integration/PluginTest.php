<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Client\Common\Plugin;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\AuthStatePlugin;
use ProgrammatorDev\Api\Test\Fixture\HeaderPlugin;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class PluginTest extends AbstractTestCase
{
    public function testConfiguredPluginsAreAppliedByPriorityOrder(): void
    {
        $client = $this->mockClient();

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
        $client = $this->mockClient();
        $api = new JsonApi($client);

        $api->setup()->plugins()->add($this->headerPlugin('user'), priority: 20);
        $api->raw()->fetch();

        $this->assertSame(['user'], $client->getLastRequest()->getHeader('X-Plugin-Order'));
    }

    public function testConfiguredPluginsWithSamePriorityAreAppliedInInsertionOrder(): void
    {
        $client = $this->mockClient();

        (new JsonApi($client))
            ->usePlugin($this->headerPlugin('first'), priority: 16)
            ->usePlugin($this->headerPlugin('second'), priority: 16)
            ->raw()
            ->fetch();

        $this->assertSame(['first', 'second'], $client->getLastRequest()->getHeader('X-Plugin-Order'));
    }

    public function testPluginPriorityCanTargetInternalAuthOrder(): void
    {
        $client = $this->mockClient();

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
        $client = $this->mockClient(
            new Response(body: '{}'),
            new Response(body: '{}')
        );
        $api = (new JsonApi($client))->usePlugin($this->headerPlugin('once'), priority: 16);

        $api->raw()->fetch();
        $api->raw()->fetch();

        $this->assertSame(['once'], $client->getRequests()[0]->getHeader('X-Plugin-Order'));
        $this->assertSame(['once'], $client->getRequests()[1]->getHeader('X-Plugin-Order'));
    }

    private function headerPlugin(string $value): Plugin
    {
        return new HeaderPlugin('X-Plugin-Order', $value, append: true);
    }

    private function authStatePlugin(string $label): Plugin
    {
        return new AuthStatePlugin($label);
    }
}
