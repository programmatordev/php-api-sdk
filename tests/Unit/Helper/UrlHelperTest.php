<?php

namespace ProgrammatorDev\Api\Test\Unit\Helper;

use ProgrammatorDev\Api\Helper\UrlHelper;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class UrlHelperTest extends AbstractTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testJoin(?string $baseUrl, string $path, string $expected): void
    {
        $this->assertSame($expected, UrlHelper::join($baseUrl, $path));
    }

    public static function urlProvider(): array
    {
        return [
            'base trailing slash and path leading slash' => [
                'https://api.example.com/',
                '/users',
                'https://api.example.com/users',
            ],
            'base without trailing slash and path without leading slash' => [
                'https://api.example.com',
                'users',
                'https://api.example.com/users',
            ],
            'base path is preserved' => [
                'https://api.example.com/v1/',
                '/users',
                'https://api.example.com/v1/users',
            ],
            'duplicate path slashes are reduced' => [
                'https://api.example.com/',
                '//users//1',
                'https://api.example.com/users/1',
            ],
            'absolute URL overrides base URL' => [
                'https://api.example.com',
                'https://other.example.com/users',
                'https://other.example.com/users',
            ],
            'scheme slashes are preserved' => [
                null,
                'https://api.example.com//users',
                'https://api.example.com//users',
            ],
            'relative path works without base URL' => [
                null,
                '/users//1',
                '/users/1',
            ],
        ];
    }

    public function testIsAbsoluteUrl(): void
    {
        $this->assertTrue(UrlHelper::isAbsoluteUrl('https://api.example.com/users'));
        $this->assertFalse(UrlHelper::isAbsoluteUrl('/users'));
    }
}
