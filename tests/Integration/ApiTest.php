<?php

namespace ProgrammatorDev\Api\Test\Integration;

use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class ApiTest extends AbstractTestCase
{
    public function testConfigCanBeSetAndReadBySdkApi(): void
    {
        $api = new class extends Api {
            public function setOptions(array $options): self
            {
                $this->config($options);

                return $this;
            }

            public function option(string $key, mixed $default = null): mixed
            {
                return $this->config()->get($key, $default);
            }

            public function options(): array
            {
                return $this->config()->all();
            }
        };

        $api
            ->setOptions(['timezone' => 'UTC'])
            ->setOptions(['units' => 'metric']);

        $this->assertSame('UTC', $api->option('timezone'));
        $this->assertSame('metric', $api->option('units'));
        $this->assertSame('en', $api->option('locale', 'en'));
        $this->assertSame([
            'timezone' => 'UTC',
            'units' => 'metric',
        ], $api->options());
    }
}
