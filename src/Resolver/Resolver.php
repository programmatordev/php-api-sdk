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
        $requestOptions = (new RequestOptions())->withPreservedUrlQuery();

        // The supplied link is not the complete request identity: the runtime
        // adds the method, base URL, and default queries to the memoization key.
        $requestKey = $this->runtime->requestKey(
            method: Method::GET,
            path: $pathOrUrl,
            pathParams: [],
            requestOptions: $requestOptions
        );

        // Memoize the response rather than mapped objects so repeated links
        // avoid HTTP requests while each mapping still creates a new object.
        return $this->responses[$requestKey] ??= $this->runtime->send(
            method: Method::GET,
            path: $pathOrUrl,
            pathParams: [],
            requestOptions: $requestOptions,
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
