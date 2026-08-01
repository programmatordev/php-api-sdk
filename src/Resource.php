<?php

namespace ProgrammatorDev\Api;

use ProgrammatorDev\Api\Builder\CacheBuilder;
use ProgrammatorDev\Api\Request\PipelineOption;
use ProgrammatorDev\Api\Request\PipelineOptions;

abstract class Resource
{
    private PipelineOptions $pipelineOptions;

    public function __construct(
        protected Runtime $runtime
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

    public function withConfig(array $values): static
    {
        $clone = clone $this;
        $clone->runtime = $this->runtime->withConfig($values);

        return $clone;
    }

    protected function endpoint(): Endpoint
    {
        return new Endpoint($this->runtime, $this->pipelineOptions);
    }

    private function withPipelineOptions(PipelineOptions $pipelineOptions): static
    {
        $clone = clone $this;
        $clone->pipelineOptions = $pipelineOptions;

        return $clone;
    }
}
