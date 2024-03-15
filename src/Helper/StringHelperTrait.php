<?php

namespace ProgrammatorDev\Api\Helper;

trait StringHelperTrait
{
    private function reduceDuplicateSlashes(string $string): string
    {
        return \preg_replace('#(^|[^:])//+#', '\\1/', $string);
    }
}