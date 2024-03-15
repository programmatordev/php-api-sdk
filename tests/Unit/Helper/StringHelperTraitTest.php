<?php

namespace ProgrammatorDev\Api\Test\Unit\Helper;

use ProgrammatorDev\Api\Helper\StringHelperTrait;
use ProgrammatorDev\Api\Test\AbstractTestCase;

class StringHelperTraitTest extends AbstractTestCase
{
    private $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->class = new class {
            use StringHelperTrait {
                reduceDuplicateSlashes as public;
            }
        };
    }

    public function testReduceDuplicateSlashes()
    {
        $this->assertSame(
            'https://example.com/path/test',
            $this->class->reduceDuplicateSlashes('https://example.com////path//test')
        );
    }
}