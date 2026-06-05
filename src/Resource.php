<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Request\RequestOptions;

abstract class Resource
{
    private RequestOptions $options;

    public function __construct(
        protected readonly Api $api,
        ?RequestOptions $options = null
    ) {
        $this->options = $options ?? new RequestOptions();
    }

    public function query(string $name, mixed $value): static
    {
        return $this->withOptions($this->options->withQuery($name, $value));
    }

    public function queries(array $query): static
    {
        return $this->withOptions($this->options->withQueries($query));
    }

    public function header(string $name, mixed $value): static
    {
        return $this->withOptions($this->options->withHeader($name, $value));
    }

    public function headers(array $headers): static
    {
        return $this->withOptions($this->options->withHeaders($headers));
    }

    protected function get(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->api->send(
            method: Method::GET,
            path: $path,
            pathParams: $pathParams,
            options: $this->options->withQueries($query)
        );
    }

    private function withOptions(RequestOptions $options): static
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }
}
