<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

class AuthStatePlugin implements Plugin
{
    public function __construct(private readonly string $label) {}

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $state = $request->hasHeader('Authorization') ? 'present' : 'missing';

        return $next($request->withAddedHeader('X-Auth-State', sprintf('%s:%s', $this->label, $state)));
    }
}
