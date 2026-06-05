<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Mock\Client;
use Http\Message\Authentication\Header as HeaderAuthentication;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Context\ErrorContext;
use Psr\Http\Message\RequestInterface;

class JsonApi extends Api
{
    public function __construct(Client $client)
    {
        parent::__construct();

        $this->setClientBuilder(new ClientBuilder($client));

        $this
            ->baseUrl('https://api.example.com')
            ->responses()
            ->json();
    }

    public function throwNotFoundErrors(): self
    {
        $this->errors()->status(404, function (ErrorContext $context): \Throwable {
            return new NotFoundException($context->response()->data()['message']);
        });

        return $this;
    }

    public function throwSimpleNotFoundErrors(): self
    {
        $this->errors()->status(404, NotFoundException::class);

        return $this;
    }

    public function throwStatusErrors(): self
    {
        $this->errors()->statuses([
            401 => InvalidApiKeyException::class,
            404 => NotFoundException::class,
        ]);

        return $this;
    }

    public function throwInvalidApiKeyErrors(): self
    {
        $this->errors()->when(function (ErrorContext $context): ?\Throwable {
            if (($context->response()->data()['code'] ?? null) !== 'invalid_api_key') {
                return null;
            }

            return new InvalidApiKeyException($context->response()->data()['message']);
        });

        return $this;
    }

    public function useBearerAuth(string $token): self
    {
        $this->auth()->bearer($token);

        return $this;
    }

    public function useBasicAuth(string $username, string $password): self
    {
        $this->auth()->basic($username, $password);

        return $this;
    }

    public function useHeaderAuth(string $name, string $value): self
    {
        $this->auth()->header($name, $value);

        return $this;
    }

    public function useQueryAuth(string $name, string $value): self
    {
        $this->auth()->query($name, $value);

        return $this;
    }

    public function useChainedAuth(string $headerName, string $headerValue): self
    {
        $this->auth()->chain(new HeaderAuthentication($headerName, $headerValue));

        return $this;
    }

    public function useCustomAuth(string $headerName, string $headerValue): self
    {
        $this->auth()->custom(function (RequestInterface $request) use ($headerName, $headerValue): RequestInterface {
            return $request->withHeader($headerName, $headerValue);
        });

        return $this;
    }

    public function raw(): RawResource
    {
        return $this->resource(RawResource::class);
    }
}
