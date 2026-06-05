<?php

namespace ProgrammatorDev\Api;

class Context
{
    public function __construct(
        private readonly Config $config = new Config()
    ) {}

    public function config(): Config
    {
        return $this->config;
    }
}
