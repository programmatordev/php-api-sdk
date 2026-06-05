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
        return $this->send(Method::GET, $path, $pathParams, $query);
    }

    protected function post(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::POST, $path, $pathParams, $query);
    }

    protected function put(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::PUT, $path, $pathParams, $query);
    }

    protected function patch(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::PATCH, $path, $pathParams, $query);
    }

    protected function delete(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::DELETE, $path, $pathParams, $query);
    }

    protected function head(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::HEAD, $path, $pathParams, $query);
    }

    protected function options(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::OPTIONS, $path, $pathParams, $query);
    }

    protected function send(string $method, string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->api->send(
            method: $method,
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
