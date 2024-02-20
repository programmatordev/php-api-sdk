<?php

namespace ProgrammatorDev\Api;

use Http\Client\Exception;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Exception\MissingConfigException;
use ProgrammatorDev\Api\Helper\StringHelperTrait;
use ProgrammatorDev\YetAnotherPhpValidator\Exception\ValidationException;
use ProgrammatorDev\YetAnotherPhpValidator\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Api
{
    use StringHelperTrait;

    private ?string $baseUrl = null;

    private ?ClientBuilder $clientBuilder = null;

    public function __construct()
    {
        $this->clientBuilder ??= new ClientBuilder();
    }

    /**
     * @throws MissingConfigException
     * @throws Exception
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        array $headers = [],
        string|StreamInterface $body = null
    ): ResponseInterface
    {
        if (!$this->getBaseUrl()) {
            throw new MissingConfigException('A base URL must be set');
        }

        return $this->clientBuilder->getClient()->send(
            $method,
            $this->createUri($path, $query),
            $headers,
            $body
        );
    }

    protected function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * @throws ValidationException
     */
    protected function setBaseUrl(string $baseUrl): self
    {
        Validator::url()->assert($baseUrl);

        $this->baseUrl = $baseUrl;

        return $this;
    }

    protected function getClientBuilder(): ?ClientBuilder
    {
        return $this->clientBuilder;
    }

    protected function setClientBuilder(ClientBuilder $clientBuilder): self
    {
        $this->clientBuilder = $clientBuilder;

        return $this;
    }

    private function createUri(string $path, array $query = []): string
    {
        $uri = $this->reduceDuplicateSlashes($this->getBaseUrl() . $path);

        if (!empty($query)) {
            $uri = \sprintf('%s?%s', $uri, \http_build_query($query));
        }

        return $uri;
    }
}