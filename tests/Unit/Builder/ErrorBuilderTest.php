<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Nyholm\Psr7\Response as PsrResponse;
use ProgrammatorDev\Api\Builder\ErrorBuilder;
use ProgrammatorDev\Api\Config;
use ProgrammatorDev\Api\Context;
use ProgrammatorDev\Api\Context\ErrorContext;
use ProgrammatorDev\Api\Response;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ErrorBuilderTest extends AbstractTestCase
{
    public function testUnmatchedStatusDoesNotThrow(): void
    {
        $builder = new ErrorBuilder();

        $builder->status(404, fn(): \Throwable => new \RuntimeException('Not found'));
        $builder->throwIfMatched($this->context(statusCode: 200));

        $this->assertTrue(true);
    }

    public function testMatchedStatusThrowsConfiguredThrowable(): void
    {
        $builder = new ErrorBuilder();
        $builder->status(404, fn(ErrorContext $context): \Throwable => new \RuntimeException(
            sprintf('Status %d in %s', $context->statusCode(), $context->context()->config()->get('timezone'))
        ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Status 404 in UTC');

        $builder->throwIfMatched($this->context(statusCode: 404));
    }

    public function testMatchedStatusThrowsConfiguredThrowableClass(): void
    {
        $builder = new ErrorBuilder();
        $builder->status(404, \RuntimeException::class);

        $this->expectException(\RuntimeException::class);

        $builder->throwIfMatched($this->context(statusCode: 404));
    }

    public function testMatchedStatusThrowsConfiguredThrowableFromStatusMap(): void
    {
        $builder = new ErrorBuilder();
        $builder->statuses([
            401 => \UnexpectedValueException::class,
            404 => fn(): \Throwable => new \RuntimeException('Missing resource'),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing resource');

        $builder->throwIfMatched($this->context(statusCode: 404));
    }

    public function testStatusHandlerRequiresThrowableClass(): void
    {
        $builder = new ErrorBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Error handler for status 404 must be a Throwable class or callable.');

        $builder->status(404, \stdClass::class);
    }

    public function testCustomHandlerThrowsWhenMatched(): void
    {
        $builder = new ErrorBuilder();
        $builder->when(function (ErrorContext $context): ?\Throwable {
            if ($context->response()->data()['code'] !== 'invalid_api_key') {
                return null;
            }

            return new \RuntimeException('Invalid API key');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid API key');

        $builder->throwIfMatched($this->context(statusCode: 401, data: ['code' => 'invalid_api_key']));
    }

    public function testCustomHandlerDoesNotThrowWhenNotMatched(): void
    {
        $builder = new ErrorBuilder();
        $builder->when(fn(): ?\Throwable => null);

        $builder->throwIfMatched($this->context(statusCode: 200));

        $this->assertTrue(true);
    }

    public function testCustomHandlerMustReturnThrowableOrNull(): void
    {
        $builder = new ErrorBuilder();
        $builder->when(fn(): string => 'invalid');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Error handler must return a Throwable or null.');

        $builder->throwIfMatched($this->context(statusCode: 200));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function context(int $statusCode, array $data = []): ErrorContext
    {
        $context = new Context(new Config(['timezone' => 'UTC']));

        return new ErrorContext(
            response: new Response($data ?? [], new PsrResponse(status: $statusCode), $context),
            context: $context
        );
    }
}
