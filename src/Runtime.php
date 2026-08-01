<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Builder\ErrorBuilder;
use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Context\ErrorContext;
use ProgrammatorDev\Api\Request\PipelineOptions;
use ProgrammatorDev\Api\Request\RequestOptions;
use ProgrammatorDev\Api\Response\Response;
use ProgrammatorDev\Api\Response\ResponseDecoder;
use Psr\Http\Client\ClientExceptionInterface;

final class Runtime
{
    /**
     * Transport is provided lazily so resources keep using the latest mutable API
     * setup instead of the setup snapshot from when the resource was created.
     *
     * @param \Closure(): \ProgrammatorDev\Api\Http\Transport $transport
     */
    public function __construct(
        private readonly Config $config,
        private readonly \Closure $transport,
        private readonly ResponseDecoder $responseDecoder,
        private readonly ErrorBuilder $errorBuilder,
        private readonly array $configOverrides = []
    ) {}

    public function config(): Config
    {
        if ($this->configOverrides !== []) {
            return (clone $this->config)->merge($this->configOverrides);
        }

        return $this->config;
    }

    public function withConfig(array $values): self
    {
        return new self(
            config: $this->config,
            transport: $this->transport,
            responseDecoder: $this->responseDecoder,
            errorBuilder: $this->errorBuilder,
            configOverrides: array_merge($this->configOverrides, $values)
        );
    }

    /**
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function send(
        string $method,
        string $path,
        array $pathParams,
        RequestOptions $requestOptions,
        PipelineOptions $pipelineOptions
    ): Response {
        $context = new Context($this->config());

        $rawResponse = ($this->transport)()->send(
            method: $method,
            path: $path,
            pathParams: $pathParams,
            options: $requestOptions,
            pipelineOptions: $pipelineOptions,
            context: $context
        );

        $response = new Response(
            data: $this->responseDecoder->decode($rawResponse),
            rawResponse: $rawResponse,
            context: $context
        );

        $this->errorBuilder->throwIfMatched(new ErrorContext($response, $context));

        return $response;
    }
}
