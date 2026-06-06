<?php

namespace ProgrammatorDev\Api\Test\Fixture\Weather;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;

class CurrentWeather implements EntityInterface
{
    public function __construct(
        private readonly string $city,
        private readonly float $temperature,
        private readonly string $description,
        private readonly ?string $units = null,
        private readonly ?string $lang = null
    ) {}

    public static function fromArray(array $data, ?Context $context = null): static
    {
        return new static(
            city: $data['name'],
            temperature: $data['main']['temp'],
            description: $data['weather'][0]['description'],
            units: $context?->config()->get('units'),
            lang: $context?->config()->get('lang')
        );
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUnits(): ?string
    {
        return $this->units;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }
}
