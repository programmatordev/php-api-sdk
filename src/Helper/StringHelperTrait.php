<?php

namespace ProgrammatorDev\Api\Helper;

trait StringHelperTrait
{
    private function reduceDoubleSlashes(string $string): string
    {
        return \preg_replace('#(^|[^:])//+#', '\\1/', $string);
    }
}