<?php

namespace ProgrammatorDev\Api\Helper;

final class UrlHelper
{
    private function __construct() {}

    public static function isAbsoluteUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function join(?string $baseUrl, string $path): string
    {
        if (self::isAbsoluteUrl($path)) {
            return $path;
        }

        if ($baseUrl === null || $baseUrl === '') {
            return self::normalizeSlashes($path);
        }

        return self::normalizeSlashes(
            rtrim($baseUrl, '/') . '/' . ltrim($path, '/')
        );
    }

    private static function normalizeSlashes(string $url): string
    {
        return preg_replace('#(^|[^:])//+#', '\\1/', $url);
    }
}
