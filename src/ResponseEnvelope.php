<?php

namespace ProgrammatorDev\Api;

interface ResponseEnvelope
{
    public static function fromResponse(Response $response): static;
}
