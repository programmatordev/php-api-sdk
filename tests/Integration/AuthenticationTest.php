<?php

namespace ProgrammatorDev\Api\Test\Integration;

use Http\Mock\Client;
use Nyholm\Psr7\Response;
use ProgrammatorDev\Api\Test\Fixture\JsonApi;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class AuthenticationTest extends AbstractTestCase
{
    public function testBearerAuthenticationAddsAuthorizationHeader(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useBearerAuth('secret')
            ->raw()
            ->fetch();

        $this->assertSame('Bearer secret', $client->getLastRequest()->getHeaderLine('Authorization'));
    }

    public function testBasicAuthenticationAddsAuthorizationHeader(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useBasicAuth('user', 'pass')
            ->raw()
            ->fetch();

        $this->assertSame('Basic ' . base64_encode('user:pass'), $client->getLastRequest()->getHeaderLine('Authorization'));
    }

    public function testHeaderAuthenticationAddsConfiguredHeader(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useHeaderAuth('X-Api-Key', 'secret')
            ->raw()
            ->fetch();

        $this->assertSame('secret', $client->getLastRequest()->getHeaderLine('X-Api-Key'));
    }

    public function testQueryAuthenticationAddsConfiguredQueryParameter(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useQueryAuth('appid', 'secret')
            ->raw()
            ->fetch();

        parse_str($client->getLastRequest()->getUri()->getQuery(), $query);

        $this->assertSame('secret', $query['appid']);
    }

    public function testWsseAuthenticationAddsWsseHeaders(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useWsseAuth('user', 'pass')
            ->raw()
            ->fetch();

        $this->assertSame('WSSE profile="UsernameToken"', $client->getLastRequest()->getHeaderLine('Authorization'));
        $this->assertStringContainsString('UsernameToken Username="user"', $client->getLastRequest()->getHeaderLine('X-WSSE'));
    }

    public function testConditionalAuthenticationAddsAuthenticationWhenMatched(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useConditionalAuth()
            ->raw()
            ->fetch();

        $this->assertSame('conditional', $client->getLastRequest()->getHeaderLine('X-Conditional-Auth'));
    }

    public function testConditionalAuthenticationDoesNotAddAuthenticationWhenUnmatched(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useConditionalAuth()
            ->raw()
            ->absolute('https://api.example.com/users');

        $this->assertSame('', $client->getLastRequest()->getHeaderLine('X-Conditional-Auth'));
    }

    public function testChainedAuthenticationCanBeUsed(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useChainedAuth('X-Chain-Auth', 'chain')
            ->raw()
            ->fetch();

        $this->assertSame('chain', $client->getLastRequest()->getHeaderLine('X-Chain-Auth'));
    }

    public function testConfiguredAuthenticationReplacesPreviousAuthentication(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useBearerAuth('secret')
            ->useQueryAuth('appid', 'key')
            ->raw()
            ->fetch();

        parse_str($client->getLastRequest()->getUri()->getQuery(), $query);

        $this->assertSame('', $client->getLastRequest()->getHeaderLine('Authorization'));
        $this->assertSame('key', $query['appid']);
    }

    public function testCustomAuthenticationCallbackCanBeUsed(): void
    {
        $client = $this->client();

        (new JsonApi($client))
            ->useCustomAuth('X-Custom-Auth', 'custom')
            ->raw()
            ->fetch();

        $this->assertSame('custom', $client->getLastRequest()->getHeaderLine('X-Custom-Auth'));
    }

    private function client(): Client
    {
        $client = new Client();
        $client->addResponse(new Response(body: '{}'));

        return $client;
    }
}
