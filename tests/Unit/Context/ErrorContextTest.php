<?php

namespace ProgrammatorDev\Api\Test\Unit\Context;

use Nyholm\Psr7\Response as PsrResponse;
use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Context\ErrorContext;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ErrorContextTest extends AbstractTestCase
{
    public function testContextReturnsResponseContextAndStatusCode(): void
    {
        $context = new Context(new Config(['timezone' => 'UTC']));
        $response = new Response([], new PsrResponse(status: 404), $context);
        $errorContext = new ErrorContext($response, $context);

        $this->assertSame($response, $errorContext->response());
        $this->assertSame($context, $errorContext->apiContext());
        $this->assertSame(404, $errorContext->statusCode());
    }
}
