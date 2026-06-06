<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

class HeaderPlugin implements Plugin
{
    public function __construct(
        private readonly string $name,
        private readonly string $value,
        private readonly bool $append = false
    ) {}

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $request = $this->append
            ? $request->withAddedHeader($this->name, $this->value)
            : $request->withHeader($this->name, $this->value);

        return $next($request);
    }
}
