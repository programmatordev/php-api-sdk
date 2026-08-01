<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Nyholm\Psr7\Request;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Rejects numeric header values that permissive PSR-7 implementations may cast.
 */
class StrictHeaderRequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new class($method, $uri) extends Request {
            public function withHeader($header, $value): MessageInterface
            {
                foreach ((array) $value as $item) {
                    if (!is_string($item)) {
                        throw new \InvalidArgumentException('Header values must be strings.');
                    }
                }

                return parent::withHeader($header, $value);
            }
        };
    }
}
