<?php

namespace ProgrammatorDev\Api\Context;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseContext
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        private readonly Context $context
    ) {}

    public function request(): RequestInterface
    {
        return $this->request;
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }

    public function apiContext(): Context
    {
        return $this->context;
    }
}
