<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\Layers;

trait InjectsResourceHelpers
{
    use RegistersInLayerProvider;

    private ?LayerResourceNamespace $layerResourceNamespace = null;

    protected function injectWithViews(): void
    {
        $namespace = $this->resourceNamespace();

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (LayerProvider $provider): string => "\$this->withViews({$provider->pathExpression(LayerFolder::Views->value)}{$namespace->argument()});",
            LayerFolder::Views->value,
            '$this->withViews(',
        );
    }

    protected function injectWithComponents(string $componentNamespace): void
    {
        $namespace = $this->resourceNamespace();
        $quotedNamespace = str_replace('\\', '\\\\', trim($componentNamespace, '\\'));

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->withComponents('{$quotedNamespace}'{$namespace->argument()});",
            $componentNamespace,
            '$this->withComponents(',
        );
    }

    protected function resourceNamespace(): LayerResourceNamespace
    {
        if ($this->layerResourceNamespace !== null) {
            return $this->layerResourceNamespace;
        }

        $namespace = LayerResourceNamespace::of($this->resolveLayer()->name, $this->laravel->make(Layers::class));

        if ($namespace->isExplicit()) {
            $this->components->warn("Another layer is also named [{$this->resolveLayer()->name->package}]: this layer's views and components use the [{$namespace->name()}] namespace.");
        }

        return $this->layerResourceNamespace = $namespace;
    }
}
