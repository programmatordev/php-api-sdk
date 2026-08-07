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

    public function get(string $pathOrUrl): Response
    {
        // Link following can be called repeatedly by entities/envelopes in the same response graph.
        // Memoize the SDK Response, not typed objects,
        // so mapping remains caller-owned while duplicate HTTP requests are avoided.
        return $this->responses[$pathOrUrl] ??= $this->runtime->send(
            method: Method::GET,
            path: $pathOrUrl,
            pathParams: [],
            requestOptions: (new RequestOptions())->withQueries($this->queryFromUrl($pathOrUrl)),
            pipelineOptions: new PipelineOptions(),
            resolver: $this
        );
    }

    public function entity(string $pathOrUrl, string $class, ?string $key = null): EntityInterface
    {
        return $this->get($pathOrUrl)->entity($class, $key);
    }

    public function collection(string $pathOrUrl, string $class, ?string $key = null): array
    {
        return $this->get($pathOrUrl)->collection($class, $key);
    }

    public function envelope(string $pathOrUrl, string $class): EnvelopeInterface
    {
        return $this->get($pathOrUrl)->envelope($class);
    }

    private function queryFromUrl(string $pathOrUrl): array
    {
        // Query values in a followed link must take precedence over API defaults because the
        // API generated that link. For example, with defaults `page=1&locale=en`, resolving
        // `/users?page=2` must request `/users?page=2&locale=en`, not page 1. Promoting the
        // link query to request-local options gives it that precedence during the normal merge.
        $query = parse_url($pathOrUrl, PHP_URL_QUERY);

        if ($query === null || $query === false || $query === '') {
            return [];
        }

        parse_str($query, $values);

        return $values;
    }
}
