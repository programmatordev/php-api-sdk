<?php

namespace ProgrammatorDev\Api\Test\Unit\Helper;

use ProgrammatorDev\Api\Helper\StringHelper;
use ProgrammatorDev\Api\Test\AbstractTestCase;

class StringHelperTest extends AbstractTestCase
{
    public function testReduceDuplicateSlashes()
    {
        $this->assertSame(
            'https://example.com/path/test',
            StringHelper::reduceDuplicateSlashes('https://example.com////path//test')
        );
    }
}