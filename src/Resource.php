<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Http\Method;
use ProgrammatorDev\Api\Request\RequestOptions;
use ProgrammatorDev\Api\Response\Response;
use Psr\Http\Message\StreamInterface;

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

    public function json(array $data): static
    {
        return $this
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function form(array $data): static
    {
        return $this
            ->header('Content-Type', 'application/x-www-form-urlencoded')
            ->body(http_build_query($data));
    }

    public function body(mixed $body): static
    {
        if (is_array($body)) {
            throw new \InvalidArgumentException('Use json() or form() to send array request data.');
        }

        if (!$body instanceof StreamInterface && !is_string($body) && $body !== null) {
            throw new \InvalidArgumentException('Request body must be a string, stream, or null.');
        }

        return $this->withOptions($this->options->withBody($body));
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

    protected function connect(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::CONNECT, $path, $pathParams, $query);
    }

    protected function trace(string $path, array $pathParams = [], array $query = []): Response
    {
        return $this->send(Method::TRACE, $path, $pathParams, $query);
    }

    protected function config(): Config
    {
        return $this->api->config();
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
