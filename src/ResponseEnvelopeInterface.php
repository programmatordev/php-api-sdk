<?php

namespace ProgrammatorDev\Api;

interface ResponseEnvelopeInterface
{
    public static function fromResponse(Response $response, ?Context $context = null): static;
}
