<?php

namespace ProgrammatorDev\Api\Builder;

use ProgrammatorDev\Api\ResponseFormat;
use Psr\Http\Message\ResponseInterface;

class ResponseBuilder
{
    private ResponseFormat $format = ResponseFormat::Raw;

    /** @var null|callable(ResponseInterface): mixed */
    private $customDecoder = null;

    public function raw(): self
    {
        $this->format = ResponseFormat::Raw;
        $this->customDecoder = null;

        return $this;
    }

    public function json(): self
    {
        $this->format = ResponseFormat::Json;
        $this->customDecoder = null;

        return $this;
    }

    public function xml(): self
    {
        $this->format = ResponseFormat::Xml;
        $this->customDecoder = null;

        return $this;
    }

    /**
     * @param callable(ResponseInterface): mixed $decoder
     */
    public function custom(callable $decoder): self
    {
        $this->format = ResponseFormat::Custom;
        $this->customDecoder = $decoder;

        return $this;
    }

    public function format(): ResponseFormat
    {
        return $this->format;
    }

    /**
     * @return null|callable(ResponseInterface): mixed
     */
    public function customDecoder(): ?callable
    {
        return $this->customDecoder;
    }
}
