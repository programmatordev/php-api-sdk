<?php

namespace ProgrammatorDev\Api\Config;

class Config
{
    public function __construct(
        private array $values = []
    ) {}

    public function all(): array
    {
        return $this->values;
    }

    public function only(string ...$keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $values[$key] = $this->values[$key];
            }
        }

        return $values;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->values[$key];
    }

    public function set(string $key, mixed $value): self
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function merge(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }
}
