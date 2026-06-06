<?php

namespace ProgrammatorDev\Api\Test\Unit;

use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ConfigTest extends AbstractTestCase
{
    public function testConfigReturnsValues(): void
    {
        $config = new Config(['timezone' => 'UTC']);

        $this->assertTrue($config->has('timezone'));
        $this->assertSame('UTC', $config->get('timezone'));
        $this->assertSame(['timezone' => 'UTC'], $config->all());
    }

    public function testConfigReturnsDefaultForMissingValue(): void
    {
        $config = new Config();

        $this->assertFalse($config->has('timezone'));
        $this->assertSame('Europe/Lisbon', $config->get('timezone', 'Europe/Lisbon'));
    }

    public function testConfigCanStoreNullValues(): void
    {
        $config = new Config(['timezone' => null]);

        $this->assertTrue($config->has('timezone'));
        $this->assertNull($config->get('timezone', 'Europe/Lisbon'));
    }

    public function testConfigCanBeUpdated(): void
    {
        $config = new Config(['timezone' => 'UTC']);

        $config
            ->set('timezone', 'Europe/Lisbon')
            ->merge(['units' => 'metric']);

        $this->assertSame([
            'timezone' => 'Europe/Lisbon',
            'units' => 'metric',
        ], $config->all());
    }
}
