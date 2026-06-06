<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Message\Formatter;
use ProgrammatorDev\Api\Builder\LoggerBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;
use Psr\Log\LoggerInterface;

class LoggerBuilderTest extends AbstractTestCase
{
    public function testDefaults()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $loggerBuilder = new LoggerBuilder($logger);

        $this->assertInstanceOf(LoggerInterface::class, $loggerBuilder->getLogger());
        $this->assertInstanceOf(Formatter\SimpleFormatter::class, $loggerBuilder->getFormatter());
    }

    public function testDependencyInjection()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formatter = $this->createMock(Formatter::class);

        $loggerBuilder = new LoggerBuilder($logger, $formatter);

        $this->assertInstanceOf(LoggerInterface::class, $loggerBuilder->getLogger());
        $this->assertInstanceOf(Formatter::class, $loggerBuilder->getFormatter());
    }

    public function testFluentMethods()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $formatter = $this->createMock(Formatter::class);

        $loggerBuilder = (new LoggerBuilder($logger))
            ->logger($logger)
            ->formatter($formatter);

        $this->assertInstanceOf(LoggerInterface::class, $loggerBuilder->getLogger());
        $this->assertInstanceOf(Formatter::class, $loggerBuilder->getFormatter());
    }
}
