<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Request\PipelineOption;
use ProgrammatorDev\Api\Request\PipelineOptions;

abstract class Resource
{
    private PipelineOptions $pipelineOptions;

    public function __construct(
        protected readonly Api $api
    ) {
        $this->pipelineOptions = new PipelineOptions();
    }

    /**
     * @param callable(CacheBuilder): mixed $configure
     */
    public function withCache(callable $configure): static
    {
        return $this->withPipelineOptions(
            $this->pipelineOptions->withOverride(PipelineOption::CACHE, $configure)
        );
    }

    protected function endpoint(): Endpoint
    {
        return new Endpoint($this->api, $this->pipelineOptions);
    }

    private function withPipelineOptions(PipelineOptions $pipelineOptions): static
    {
        $clone = clone $this;
        $clone->pipelineOptions = $pipelineOptions;

        return $clone;
    }
}
