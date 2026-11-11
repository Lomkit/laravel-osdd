<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerFolder;

trait InjectsResourceHelpers
{
    use RegistersInLayerProvider;

    protected function warnWhenTheResourceNamespaceIsShared(): void
    {
        $target = $this->target();

        if ($target->resourceNamespace->isExplicit()) {
            $this->components->warn("Another layer is also named [{$target->layer->name->package}]: this layer's views and components use the [{$target->resourceNamespace->name()}] namespace.");
        }
    }

    protected function injectWithViews(): void
    {
        $namespace = $this->target()->resourceNamespace;

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (LayerProvider $provider): string => "\$this->withViews({$provider->pathExpression(LayerFolder::Views->value)}{$namespace->argument()});",
            LayerFolder::Views->value,
            '$this->withViews(',
        );
    }

    protected function injectWithComponents(string $componentNamespace): void
    {
        $namespace = $this->target()->resourceNamespace;
        $quotedNamespace = str_replace('\\', '\\\\', trim($componentNamespace, '\\'));

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->withComponents('{$quotedNamespace}'{$namespace->argument()});",
            $componentNamespace,
            '$this->withComponents(',
        );
    }
}
