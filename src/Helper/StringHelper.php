<?php

namespace ProgrammatorDev\Api\Helper;

class StringHelper
{
    public static function reduceDuplicateSlashes(string $string): string
    {
        return preg_replace('#(^|[^:])//+#', '\\1/', $string);
    }
}