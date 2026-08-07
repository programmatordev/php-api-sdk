<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Http\Method;
use ProgrammatorDev\Api\Request\PipelineOption;
use ProgrammatorDev\Api\Request\PipelineOptions;
use ProgrammatorDev\Api\Request\RequestOptions;
use ProgrammatorDev\Api\Response\Response;
use Psr\Http\Message\StreamInterface;

class Endpoint
{
    private RequestOptions $options;

    public function __construct(
        private readonly Runtime $runtime,
        private PipelineOptions $pipelineOptions
    ) {
        $this->options = new RequestOptions();
    }

    /**
     * @param callable(\ProgrammatorDev\Api\Builder\CacheBuilder): mixed $configure
     */
    public function withCache(callable $configure): static
    {
        return $this->withPipelineOptions(
            $this->pipelineOptions->withDefault(PipelineOption::CACHE, $configure)
        );
    }

    /**
     * @deprecated since 3.2.0. Use withCache().
     *
     * @param callable(\ProgrammatorDev\Api\Builder\CacheBuilder): mixed $configure
     */
    public function cache(callable $configure): static
    {
        return $this->withCache($configure);
    }

    /**
     * @throws \JsonException
     */
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

    /**
     * @throws \InvalidArgumentException
     */
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

    /**
     * @throws \Throwable
     */
    public function get(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::GET, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function post(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::POST, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function put(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::PUT, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function patch(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::PATCH, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function delete(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::DELETE, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function head(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::HEAD, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function options(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::OPTIONS, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function connect(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::CONNECT, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    public function trace(string $path, array $pathParams = []): Response
    {
        return $this->send(Method::TRACE, $path, $pathParams);
    }

    /**
     * @throws \Throwable
     */
    private function send(string $method, string $path, array $pathParams = []): Response
    {
        return $this->runtime->send(
            method: $method,
            path: $path,
            pathParams: $pathParams,
            requestOptions: $this->options,
            pipelineOptions: $this->pipelineOptions
        );
    }

    private function withOptions(RequestOptions $options): static
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    private function withPipelineOptions(PipelineOptions $pipelineOptions): static
    {
        $clone = clone $this;
        $clone->pipelineOptions = $pipelineOptions;

        return $clone;
    }
}
