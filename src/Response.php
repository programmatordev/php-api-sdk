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
        if (!is_subclass_of($class, Entity::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Entity class "%s" must implement %s.',
                $class,
                Entity::class
            ));
        }

        $data = $key === null ? $this->data : $this->data[$key];

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Entity data must be an array.');
        }

        return $class::fromArray($data);
    }
}
