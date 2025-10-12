<?php

namespace ProgrammatorDev\Api\Helper;

class StringHelper
{
    public static function reduceDuplicateSlashes(string $string): string
    {
        return preg_replace('#(^|[^:])//+#', '\\1/', $string);
    }

    public static function isUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }
}