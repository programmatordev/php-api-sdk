<?php

namespace ProgrammatorDev\Api\Builder;

use Http\Client\Common\Plugin;

class PluginBuilder
{
    /** @var array<int, list<Plugin>> */
    private array $plugins = [];

    public function add(Plugin $plugin, int $priority = 0): self
    {
        $this->plugins[$priority] ??= [];
        $this->plugins[$priority][] = $plugin;

        return $this;
    }

    public function merge(self $builder): self
    {
        foreach ($builder->entries() as $priority => $plugins) {
            foreach ($plugins as $plugin) {
                $this->add($plugin, $priority);
            }
        }

        return $this;
    }

    /**
     * @return array<int, list<Plugin>>
     */
    public function entries(): array
    {
        return $this->plugins;
    }

    /**
     * @return list<Plugin>
     */
    public function all(): array
    {
        if ($this->plugins === []) {
            return [];
        }

        $plugins = $this->plugins;
        krsort($plugins);

        return array_values(array_merge(...array_values($plugins)));
    }
}
