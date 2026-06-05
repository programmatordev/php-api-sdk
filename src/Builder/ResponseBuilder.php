<?php

namespace ProgrammatorDev\Api\Builder;

class ResponseBuilder
{
    private bool $decodeJson = false;

    public function json(): self
    {
        $this->decodeJson = true;

        return $this;
    }

    public function shouldDecodeJson(): bool
    {
        return $this->decodeJson;
    }
}
