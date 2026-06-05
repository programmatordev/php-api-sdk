<?php

namespace ProgrammatorDev\Api;

interface Entity
{
    public static function fromArray(array $data): static;
}
