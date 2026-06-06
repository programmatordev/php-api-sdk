<?php

namespace ProgrammatorDev\Api\Contract;

use ProgrammatorDev\Api\Context\Context;

interface EntityInterface
{
    public static function fromArray(array $data, ?Context $context = null): static;
}
