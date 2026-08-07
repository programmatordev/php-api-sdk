<?php

namespace ProgrammatorDev\Api\Test\Unit;

use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;
use ProgrammatorDev\Api\Contract\ResolverInterface;
use ProgrammatorDev\Api\Response\Response;
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

    public function testContextReturnsProvidedResolver(): void
    {
        $resolver = new class implements ResolverInterface {
            public function get(string $pathOrUrl): Response
            {
                throw new \RuntimeException('Not used.');
            }

            public function entity(string $pathOrUrl, string $class, ?string $key = null): EntityInterface
            {
                throw new \RuntimeException('Not used.');
            }

            public function collection(string $pathOrUrl, string $class, ?string $key = null): array
            {
                throw new \RuntimeException('Not used.');
            }

            public function envelope(string $pathOrUrl, string $class): EnvelopeInterface
            {
                throw new \RuntimeException('Not used.');
            }
        };

        $context = new Context(resolver: $resolver);

        $this->assertTrue($context->hasResolver());
        $this->assertSame($resolver, $context->resolver());
    }

    public function testContextThrowsWhenResolverIsUnavailable(): void
    {
        $context = new Context();

        $this->assertFalse($context->hasResolver());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Response resolver is not available outside an API runtime request.');

        $context->resolver();
    }
}
