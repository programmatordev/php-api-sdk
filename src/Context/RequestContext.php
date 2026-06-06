<?php

namespace ProgrammatorDev\Api\Context;

use Psr\Http\Message\RequestInterface;

class RequestContext
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Context $context
    ) {}

    public function request(): RequestInterface
    {
        return $this->request;
    }

    public function apiContext(): Context
    {
        return $this->context;
    }
}
