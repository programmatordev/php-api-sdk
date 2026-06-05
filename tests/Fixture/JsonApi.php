<?php

namespace ProgrammatorDev\Api\Test\Fixture;

use Http\Mock\Client;
use ProgrammatorDev\Api\Api;
use ProgrammatorDev\Api\Builder\ClientBuilder;
use ProgrammatorDev\Api\Context\ErrorContext;

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

    public function raw(): RawResource
    {
        return $this->resource(RawResource::class);
    }
}
