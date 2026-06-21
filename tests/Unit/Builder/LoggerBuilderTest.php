<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Message\Formatter;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Log\LoggerInterface;

class LoggerBuilderTest extends AbstractTestCase
{
    public function testLoggerBuilderUsesDefaults(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $loggerBuilder = new LoggerBuilder($logger);

        $this->assertSame($logger, $loggerBuilder->getLogger());
        $this->assertInstanceOf(Formatter\SimpleFormatter::class, $loggerBuilder->getFormatter());
    }

    public function testLoggerBuilderAcceptsConstructorValues(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formatter = $this->createMock(Formatter::class);

        $loggerBuilder = new LoggerBuilder($logger, $formatter);

        $this->assertSame($logger, $loggerBuilder->getLogger());
        $this->assertSame($formatter, $loggerBuilder->getFormatter());
    }

    public function testLoggerBuilderCanBeConfiguredFluently(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formatter = $this->createMock(Formatter::class);

        $loggerBuilder = (new LoggerBuilder($logger))
            ->logger($logger)
            ->formatter($formatter);

        $this->assertSame($logger, $loggerBuilder->getLogger());
        $this->assertSame($formatter, $loggerBuilder->getFormatter());
    }
}
