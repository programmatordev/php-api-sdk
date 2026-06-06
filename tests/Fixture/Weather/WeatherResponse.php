<?php

namespace ProgrammatorDev\Api\Test\Fixture\Weather;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\ResponseEnvelopeInterface;
use ProgrammatorDev\Api\Response\Response;

class WeatherResponse implements ResponseEnvelopeInterface
{
    public function __construct(
        private readonly CurrentWeather $weather,
        private readonly int $statusCode,
        private readonly ?string $units = null
    ) {}

    public static function fromResponse(Response $response, ?Context $context = null): static
    {
        return new static(
            weather: $response->entity(CurrentWeather::class),
            statusCode: $response->raw()->getStatusCode(),
            units: $context?->config()->get('units')
        );
    }

    public function getWeather(): CurrentWeather
    {
        return $this->weather;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getUnits(): ?string
    {
        return $this->units;
    }
}
