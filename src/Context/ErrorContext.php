<?php

namespace ProgrammatorDev\Api\Context;

use ProgrammatorDev\Api\Response\Response;

class ErrorContext
{
    public function __construct(
        private readonly Response $response,
        private readonly Context $context
    ) {}

    public function response(): Response
    {
        return $this->response;
    }

    public function context(): Context
    {
        return $this->context;
    }

    public function statusCode(): int
    {
        return $this->response->raw()->getStatusCode();
    }
}
