<?php

namespace ProgrammatorDev\Api\Test\Unit\Builder;

use Http\Client\Common\Plugin;
use ProgrammatorDev\Api\Builder\PluginBuilder;
use ProgrammatorDev\Api\Test\Support\AbstractTestCase;

class PluginBuilderTest extends AbstractTestCase
{
    public function testPluginsAreReturnedByDescendingPriority(): void
    {
        $low = $this->createMock(Plugin::class);
        $high = $this->createMock(Plugin::class);
        $middle = $this->createMock(Plugin::class);

        $plugins = (new PluginBuilder())
            ->add($low, priority: 8)
            ->add($high, priority: 40)
            ->add($middle, priority: 16)
            ->getPlugins();

        $this->assertSame([$high, $middle, $low], $plugins);
    }

    public function testPluginsWithSamePriorityArePreservedInInsertionOrder(): void
    {
        $first = $this->createMock(Plugin::class);
        $second = $this->createMock(Plugin::class);

        $plugins = (new PluginBuilder())
            ->add($first, priority: 16)
            ->add($second, priority: 16)
            ->getPlugins();

        $this->assertSame([$first, $second], $plugins);
    }

    public function testPluginBuildersCanBeMergedWithoutLosingPriority(): void
    {
        $low = $this->createMock(Plugin::class);
        $high = $this->createMock(Plugin::class);

        $source = (new PluginBuilder())->add($high, priority: 40);

        $plugins = (new PluginBuilder())
            ->add($low, priority: 8)
            ->merge($source)
            ->getPlugins();

        $this->assertSame([$high, $low], $plugins);
    }
}
