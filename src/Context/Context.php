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

    public function hasResolver(): bool
    {
        return $this->resolver !== null;
    }

    public function resolver(): ResolverInterface
    {
        if ($this->resolver === null) {
            // Manually-created contexts can still exist for tests or standalone hydration,
            // but link resolution requires an API runtime request.
            throw new \RuntimeException('Response resolver is not available outside an API runtime request.');
        }

        return $this->resolver;
    }
}
