<?php

namespace ProgrammatorDev\Api\Test\Unit;

use ProgrammatorDev\Api\Request\PipelineOptions;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class PipelineOptionsTest extends AbstractTestCase
{
    public function testPipelineOptionsApplyDefaultsBeforeOverrides(): void
    {
        $builder = new class {
            public array $values = [];

            public function add(string $value): void
            {
                $this->values[] = $value;
            }
        };

        $options = (new PipelineOptions())
            ->withOverride('feature', fn(object $builder) => $builder->add('override'))
            ->withDefault('feature', fn(object $builder) => $builder->add('default'));

        $this->assertTrue($options->has('feature'));
        $this->assertFalse($options->has('other'));

        $this->assertSame($builder, $options->applyTo('feature', $builder));
        $this->assertSame(['default', 'override'], $builder->values);
    }

    public function testPipelineOptionsIgnoreUnrelatedKeys(): void
    {
        $builder = new class {
            public array $values = [];
        };

        $options = (new PipelineOptions())
            ->withDefault('feature', fn(object $builder) => $builder->values[] = 'feature');

        $options->applyTo('other', $builder);

        $this->assertSame([], $builder->values);
    }
}
