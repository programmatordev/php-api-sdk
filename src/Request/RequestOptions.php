<?php

namespace ProgrammatorDev\Api\Request;

use Psr\Http\Message\StreamInterface;

class RequestOptions
{
    public function __construct(
        private readonly array $query = [],
        private readonly array $headers = [],
        private readonly string|StreamInterface|null $body = null,
        private readonly bool $preserveUrlQuery = false
    ) {}

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string|StreamInterface|null
    {
        return $this->body;
    }

    public function shouldPreserveUrlQuery(): bool
    {
        return $this->preserveUrlQuery;
    }

    public function withQuery(string $name, mixed $value): self
    {
        return $this->withQueries([$name => $value]);
    }

    public function withQueries(array $query): self
    {
        return new self(
            query: array_merge($this->query, $this->filterNullValues($query)),
            headers: $this->headers,
            body: $this->body,
            preserveUrlQuery: $this->preserveUrlQuery
        );
    }

    public function withHeader(string $name, mixed $value): self
    {
        return $this->withHeaders([$name => $value]);
    }

    public function withHeaders(array $headers): self
    {
        return new self(
            query: $this->query,
            headers: array_merge($this->headers, $headers),
            body: $this->body,
            preserveUrlQuery: $this->preserveUrlQuery
        );
    }

    public function withBody(string|StreamInterface|null $body): self
    {
        return new self(
            query: $this->query,
            headers: $this->headers,
            body: $body,
            preserveUrlQuery: $this->preserveUrlQuery
        );
    }

    public function withPreservedUrlQuery(): self
    {
        return new self(
            query: $this->query,
            headers: $this->headers,
            body: $this->body,
            preserveUrlQuery: true
        );
    }

    private function filterNullValues(array $values): array
    {
        // Null means "omit this request-local query value"; false, 0, and empty
        // strings are still meaningful values and must be preserved.
        return array_filter($values, static fn(mixed $value): bool => $value !== null);
    }
}
