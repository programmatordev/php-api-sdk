<?php

namespace ProgrammatorDev\Api\Event;

use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PreRequestEvent extends Event
{
    public function __construct(
        private RequestInterface $request
    ) {}

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }
}