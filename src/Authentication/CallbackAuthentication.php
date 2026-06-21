<?php

namespace ProgrammatorDev\Api\Authentication;

use Http\Message\Authentication;
use Psr\Http\Message\RequestInterface;

class CallbackAuthentication implements Authentication
{
    /**
     * @param callable(RequestInterface): RequestInterface $callback
     */
    public function __construct(
        private $callback
    ) {}

    public function authenticate(RequestInterface $request)
    {
        $authenticatedRequest = ($this->callback)($request);

        if (! $authenticatedRequest instanceof RequestInterface) {
            throw new \UnexpectedValueException('Custom authentication callback must return a PSR-7 request.');
        }

        return $authenticatedRequest;
    }
}
