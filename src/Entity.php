<?php

namespace ProgrammatorDev\Api;

interface Entity
{
    public static function fromArray(array $data, ?Context $context = null): static;
}
