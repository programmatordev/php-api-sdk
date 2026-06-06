<?php

namespace ProgrammatorDev\Api;

interface EntityInterface
{
    public static function fromArray(array $data, ?Context $context = null): static;
}
