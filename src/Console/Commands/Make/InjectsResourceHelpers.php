<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\Statements\WithComponents;
use Lomkit\Osdd\Console\Providers\Statements\WithViews;

trait InjectsResourceHelpers
{
    protected function warnWhenTheResourceNamespaceIsShared(): void
    {
        $target = $this->target();

        if ($target->resourceNamespace->isExplicit()) {
            $this->components->warn("Another layer is also named [{$target->layer->name->package}]: this layer's views and components use the [{$target->resourceNamespace->name()}] namespace.");
        }
    }

    protected function injectWithViews(): void
    {
        $this->wireInTargetLayer(new WithViews($this->target()->resourceNamespace));
    }

    protected function injectWithComponents(string $componentNamespace): void
    {
        $this->wireInTargetLayer(new WithComponents($componentNamespace, $this->target()->resourceNamespace));
    }
}
