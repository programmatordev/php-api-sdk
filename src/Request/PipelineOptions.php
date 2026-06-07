<?php

namespace ProgrammatorDev\Api\Request;

class PipelineOptions
{
    /**
     * @param array<string, list<callable(object): mixed>> $defaults
     * @param array<string, list<callable(object): mixed>> $overrides
     */
    public function __construct(
        private readonly array $defaults = [],
        private readonly array $overrides = []
    ) {}

    public function has(string $key): bool
    {
        return !empty($this->defaults[$key]) || !empty($this->overrides[$key]);
    }

    /**
     * @param callable(object): mixed $configure
     */
    public function withDefault(string $key, callable $configure): self
    {
        $defaults = $this->defaults;
        $defaults[$key][] = $configure;

        return new self($defaults, $this->overrides);
    }

    /**
     * @param callable(object): mixed $configure
     */
    public function withOverride(string $key, callable $configure): self
    {
        $overrides = $this->overrides;
        $overrides[$key][] = $configure;

        return new self($this->defaults, $overrides);
    }

    /**
     * @template T of object
     * @param T $builder
     * @return T
     */
    public function applyTo(string $key, object $builder): object
    {
        foreach ($this->configurers($key) as $configure) {
            $configure($builder);
        }

        return $builder;
    }

    /**
     * @return list<callable(object): mixed>
     */
    private function configurers(string $key): array
    {
        return [
            ...($this->defaults[$key] ?? []),
            ...($this->overrides[$key] ?? []),
        ];
    }
}
