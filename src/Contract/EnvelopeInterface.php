<?php

namespace ProgrammatorDev\Api\Contract;

use ProgrammatorDev\Api\Context\Context;
use ProgrammatorDev\Api\Response\Response;

interface EnvelopeInterface
{
    public static function fromResponse(Response $response, ?Context $context = null): static;
}
