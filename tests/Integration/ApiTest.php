<?php

namespace ProgrammatorDev\Api\Test\Integration;

use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ApiTest extends AbstractTestCase
{
    public function testConfigCanBeSetAndReadBySdkApi(): void
    {
        $api = new class extends Api {};

        $api
            ->config(['timezone' => 'UTC'])
            ->merge(['units' => 'metric']);

        $this->assertSame('UTC', $api->config()->get('timezone'));
        $this->assertSame('metric', $api->config()->get('units'));
        $this->assertSame('en', $api->config()->get('locale', 'en'));
        $this->assertSame([
            'timezone' => 'UTC',
            'units' => 'metric',
        ], $api->config()->all());
    }
}
