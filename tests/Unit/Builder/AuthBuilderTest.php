<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Message\Authentication\Chain;
use Http\Message\Authentication\Header;
use Http\Message\RequestMatcher\RequestMatcher;
use Nyholm\Psr7\Request;
use ProgrammatorDev\Api\Builder\AuthBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class AuthBuilderTest extends AbstractTestCase
{
    public function testAuthenticationIsNullWhenNoAuthWasConfigured(): void
    {
        $this->assertNull((new AuthBuilder())->getAuthentication());
    }

    public function testSingleAuthenticationIsReturnedDirectly(): void
    {
        $authentication = (new AuthBuilder())
            ->bearer('token')
            ->getAuthentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com'));

        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
    }

    public function testAuthenticationHelpersReplacePreviousAuthentication(): void
    {
        $authentication = (new AuthBuilder())
            ->bearer('token')
            ->query('appid', 'key')
            ->getAuthentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('', $request->getHeaderLine('Authorization'));
        $this->assertSame('appid=key', $request->getUri()->getQuery());
    }

    public function testExplicitChainComposesHttplugAuthenticationObjects(): void
    {
        $authentication = (new AuthBuilder())
            ->chain(
                new Header('X-Api-Key', 'secret'),
                new Header('X-Second-Auth', 'second')
            )
            ->getAuthentication();

        $this->assertInstanceOf(Chain::class, $authentication);

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('secret', $request->getHeaderLine('X-Api-Key'));
        $this->assertSame('second', $request->getHeaderLine('X-Second-Auth'));
    }

    public function testUseReplacesAuthenticationWithHttplugAuthenticationObject(): void
    {
        $authentication = (new AuthBuilder())
            ->bearer('token')
            ->use(new Header('X-Api-Key', 'secret'))
            ->getAuthentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('', $request->getHeaderLine('Authorization'));
        $this->assertSame('secret', $request->getHeaderLine('X-Api-Key'));
    }

    public function testWsseAuthenticationAddsWsseHeaders(): void
    {
        $authentication = (new AuthBuilder())
            ->wsse('user', 'pass')
            ->getAuthentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('WSSE profile="UsernameToken"', $request->getHeaderLine('Authorization'));
        $this->assertStringContainsString('UsernameToken Username="user"', $request->getHeaderLine('X-WSSE'));
    }

    public function testConditionalAuthenticationOnlyAppliesWhenMatched(): void
    {
        $authentication = (new AuthBuilder())
            ->conditional(new RequestMatcher(path: '^/admin'), new Header('X-Admin-Auth', 'secret'))
            ->getAuthentication();

        $matched = $authentication->authenticate(new Request('GET', 'https://api.example.com/admin/users'));
        $unmatched = $authentication->authenticate(new Request('GET', 'https://api.example.com/users'));

        $this->assertSame('secret', $matched->getHeaderLine('X-Admin-Auth'));
        $this->assertSame('', $unmatched->getHeaderLine('X-Admin-Auth'));
    }

    public function testCustomAuthenticationUsesCallback(): void
    {
        $authentication = (new AuthBuilder())
            ->custom(fn(Request $request) => $request->withHeader('X-Custom-Auth', 'custom'))
            ->getAuthentication();

        $request = $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));

        $this->assertSame('custom', $request->getHeaderLine('X-Custom-Auth'));
    }

    public function testCustomAuthenticationCallbackMustReturnRequest(): void
    {
        $authentication = (new AuthBuilder())
            ->custom(fn() => null)
            ->getAuthentication();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Custom authentication callback must return a PSR-7 request.');

        $authentication->authenticate(new Request('GET', 'https://api.example.com/weather'));
    }
}
