<?php

namespace ProgrammatorDev\Api\Test\Unit;

use ProgrammatorDev\Api\Config;
use ProgrammatorDev\Api\Context;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ContextTest extends AbstractTestCase
{
    public function testContextReturnsEmptyConfigByDefault(): void
    {
        $context = new Context();

        $this->assertFalse($context->config()->has('timezone'));
    }

    public function testContextReturnsProvidedConfig(): void
    {
        $config = new Config(['timezone' => 'UTC']);
        $context = new Context($config);

        $this->assertSame($config, $context->config());
        $this->assertSame('UTC', $context->config()->get('timezone'));
    }
}
