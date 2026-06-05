<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Message\Authentication\Chain;
use Http\Message\Authentication\Header;
use Nyholm\Psr7\Request;
use ProgrammatorDev\Api\Builder\AuthBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class AuthBuilderTest extends AbstractTestCase
{
    public function testAuthenticationIsNullWhenNoAuthWasConfigured(): void
    {
        $this->assertNull((new AuthBuilder())->authentication());
    }

    public function testSingleAuthenticationIsReturnedDirectly(): void
    {
        $authentication = (new AuthBuilder())
            ->bearer('token')
            ->authentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com'));

        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
    }

    public function testMultipleAuthenticationsAreReturnedAsChain(): void
    {
        $authentication = (new AuthBuilder())
            ->bearer('token')
            ->query('appid', 'key')
            ->authentication();

        $this->assertInstanceOf(Chain::class, $authentication);

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
        $this->assertSame('appid=key', $request->getUri()->getQuery());
    }

    public function testExplicitChainAcceptsHttplugAuthenticationObjects(): void
    {
        $authentication = (new AuthBuilder())
            ->chain(new Header('X-Api-Key', 'secret'))
            ->authentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('secret', $request->getHeaderLine('X-Api-Key'));
    }

    public function testCustomAuthenticationUsesCallback(): void
    {
        $authentication = (new AuthBuilder())
            ->custom(fn(Request $request) => $request->withHeader('X-Custom-Auth', 'custom'))
            ->authentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('custom', $request->getHeaderLine('X-Custom-Auth'));
    }
}
