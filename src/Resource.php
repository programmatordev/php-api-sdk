<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Config\Config;

abstract class Resource
{
    public function __construct(
        protected readonly Api $api
    ) {}

    protected function endpoint(): Endpoint
    {
        return Endpoint::for($this->api);
    }

    protected function config(): Config
    {
        return $this->api->config();
    }
}
