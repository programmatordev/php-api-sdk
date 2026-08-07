<?php

namespace ProgrammatorDev\Api\Context;

use ProgrammatorDev\Api\Config\Config;
use ProgrammatorDev\Api\Contract\ResolverInterface;

class Context
{
    public function __construct(
        private readonly Config $config = new Config(),
        private readonly ?ResolverInterface $resolver = null
    ) {}

    public function config(): Config
    {
        return $this->config;
    }

    public function resolver(): ResolverInterface
    {
        if ($this->resolver === null) {
            // Resolver-backed behavior is available only within an API runtime request.
            throw new \RuntimeException('Response resolver is not available outside an API runtime request.');
        }

        return $this->resolver;
    }
}
