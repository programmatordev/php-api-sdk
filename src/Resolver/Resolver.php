<?php

namespace ProgrammatorDev\Api\Resolver;

use ProgrammatorDev\Api\Contract\EntityInterface;
use ProgrammatorDev\Api\Contract\EnvelopeInterface;
use ProgrammatorDev\Api\Contract\ResolverInterface;
use ProgrammatorDev\Api\Http\Method;
use ProgrammatorDev\Api\Request\PipelineOptions;
use ProgrammatorDev\Api\Request\RequestOptions;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Runtime;

final class Resolver implements ResolverInterface
{
    /** @var array<string, Response> */
    private array $responses = [];

    public function __construct(
        private readonly Runtime $runtime
    ) {}

    /**
     * @throws \Throwable
     */
    public function get(string $pathOrUrl): Response
    {
        // Entities and envelopes may follow the same link repeatedly within one response graph.
        // Memoize the SDK Response rather than mapped objects so mapping remains caller-owned
        // while duplicate HTTP requests are avoided.
        return $this->responses[$pathOrUrl] ??= $this->runtime->send(
            method: Method::GET,
            path: $pathOrUrl,
            pathParams: [],
            requestOptions: (new RequestOptions())->withPreservedUrlQuery(),
            pipelineOptions: new PipelineOptions(),
            resolver: $this
        );
    }

    /**
     * @template T of EntityInterface
     * @param class-string<T> $class
     * @return T
     * @throws \Throwable
     */
    public function entity(string $pathOrUrl, string $class, ?string $key = null): EntityInterface
    {
        return $this->get($pathOrUrl)->entity($class, $key);
    }

    /**
     * @template T of EntityInterface
     * @param class-string<T> $class
     * @return T[]
     * @throws \Throwable
     */
    public function collection(string $pathOrUrl, string $class, ?string $key = null): array
    {
        return $this->get($pathOrUrl)->collection($class, $key);
    }

    /**
     * @template T of EnvelopeInterface
     * @param class-string<T> $class
     * @return T
     * @throws \Throwable
     */
    public function envelope(string $pathOrUrl, string $class): EnvelopeInterface
    {
        return $this->get($pathOrUrl)->envelope($class);
    }
}
