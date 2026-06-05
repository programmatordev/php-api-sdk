<?php

namespace ProgrammatorDev\Api;

use Psr\Http\Message\ResponseInterface;

class Response
{
    public function __construct(
        private readonly mixed $data,
        private readonly ResponseInterface $rawResponse
    ) {}

    public function data(): mixed
    {
        return $this->data;
    }

    public function raw(): ResponseInterface
    {
        return $this->rawResponse;
    }

    /**
     * @template T of Entity
     * @param class-string<T> $class
     * @return T
     */
    public function entity(string $class, ?string $key = null): Entity
    {
        $this->assertEntityClass($class);

        $data = $this->getData($key);

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Entity data must be an array.');
        }

        return $class::fromArray($data);
    }

    /**
     * @template T of Entity
     * @param class-string<T> $class
     * @return T[]
     */
    public function collection(string $class, ?string $key = null): array
    {
        $this->assertEntityClass($class);

        $items = $this->getData($key);

        if (!is_array($items)) {
            throw new \UnexpectedValueException('Collection data must be an array.');
        }

        return array_map(static function (mixed $item) use ($class): Entity {
            if (!is_array($item)) {
                throw new \UnexpectedValueException('Collection item data must be an array.');
            }

            return $class::fromArray($item);
        }, $items);
    }

    /**
     * @template T of ResponseEnvelope
     * @param class-string<T> $class
     * @return T
     */
    public function as(string $class): ResponseEnvelope
    {
        $this->assertResponseEnvelopeClass($class);

        return $class::fromResponse($this);
    }

    private function getData(?string $key): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        if (!is_array($this->data) || !array_key_exists($key, $this->data)) {
            throw new \UnexpectedValueException(sprintf(
                'Response data key "%s" does not exist.',
                $key
            ));
        }

        return $this->data[$key];
    }

    /**
     * @param class-string $class
     */
    private function assertEntityClass(string $class): void
    {
        if (!is_subclass_of($class, Entity::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Entity class "%s" must implement %s.',
                $class,
                Entity::class
            ));
        }
    }

    /**
     * @param class-string $class
     */
    private function assertResponseEnvelopeClass(string $class): void
    {
        if (!is_subclass_of($class, ResponseEnvelope::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Response envelope class "%s" must implement %s.',
                $class,
                ResponseEnvelope::class
            ));
        }
    }
}
