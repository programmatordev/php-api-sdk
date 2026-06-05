<?php

namespace ProgrammatorDev\Api\Request;

use Psr\Http\Message\StreamInterface;

class RequestOptions
{
    public function __construct(
        private readonly array $query = [],
        private readonly array $headers = [],
        private readonly string|StreamInterface|null $body = null
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

    public function withQuery(string $name, mixed $value): self
    {
        return $this->withQueries([$name => $value]);
    }

    public function withQueries(array $query): self
    {
        return new self(
            query: array_merge($this->query, $this->filterNullValues($query)),
            headers: $this->headers,
            body: $this->body
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
            body: $this->body
        );
    }

    public function withBody(string|StreamInterface|null $body): self
    {
        return new self(
            query: $this->query,
            headers: $this->headers,
            body: $body
        );
    }

    private function filterNullValues(array $values): array
    {
        return array_filter($values, static fn(mixed $value): bool => $value !== null);
    }
}
