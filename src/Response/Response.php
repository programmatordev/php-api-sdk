<?php

namespace ProgrammatorDev\Api\Response;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Contract\EntityInterface;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;
use Psr\Http\Message\ResponseInterface;

class Response
{
    public function __construct(
        private readonly mixed $data,
        private readonly ResponseInterface $rawResponse,
        ?Context $context = null
    ) {
        $this->context = $context ?? new Context();
    }

    private readonly Context $context;

    public function data(): mixed
    {
        return $this->data;
    }

    public function raw(): ResponseInterface
    {
        return $this->rawResponse;
    }

    /**
     * @template T of EntityInterface
     * @param class-string<T> $class
     * @return T
     */
    public function entity(string $class, ?string $key = null): EntityInterface
    {
        $this->assertEntityClass($class);

        $data = $this->getData($key);

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Entity data must be an array.');
        }

        return $class::fromArray($data, $this->context);
    }

    /**
     * @template T of EntityInterface
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

        $context = $this->context;

        return array_map(static function (mixed $item) use ($class, $context): EntityInterface {
            if (!is_array($item)) {
                throw new \UnexpectedValueException('Collection item data must be an array.');
            }

            return $class::fromArray($item, $context);
        }, $items);
    }

    /**
     * @template T of EnvelopeInterface
     * @param class-string<T> $class
     * @return T
     */
    public function envelope(string $class): EnvelopeInterface
    {
        $this->assertEnvelopeClass($class);

        return $class::fromResponse($this, $this->context);
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
        if (!is_subclass_of($class, EntityInterface::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Entity class "%s" must implement %s.',
                $class,
                EntityInterface::class
            ));
        }
    }

    /**
     * @param class-string $class
     */
    private function assertEnvelopeClass(string $class): void
    {
        if (!is_subclass_of($class, EnvelopeInterface::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Envelope class "%s" must implement %s.',
                $class,
                EnvelopeInterface::class
            ));
        }
    }
}
