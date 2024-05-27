<?php

namespace ProgrammatorDev\Api\Event;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PostRequestEvent extends Event
{
    public function __construct(
        private readonly RequestInterface $request,
        private ResponseInterface $response
    ) {}

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}