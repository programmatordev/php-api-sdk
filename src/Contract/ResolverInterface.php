<?php

namespace ProgrammatorDev\Api\Contract;

use ProgrammatorDev\Api\Response\Response;

interface ResolverInterface
{
    public function get(string $pathOrUrl): Response;

    /**
     * @template T of EntityInterface
     * @param class-string<T> $class
     * @return T
     */
    public function entity(string $pathOrUrl, string $class, ?string $key = null): EntityInterface;

    /**
     * @template T of EntityInterface
     * @param class-string<T> $class
     * @return T[]
     */
    public function collection(string $pathOrUrl, string $class, ?string $key = null): array;

    /**
     * @template T of EnvelopeInterface
     * @param class-string<T> $class
     * @return T
     */
    public function envelope(string $pathOrUrl, string $class): EnvelopeInterface;
}
