<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayersCollection;

trait InjectsResourceHelpers
{
    use RegistersInLayerProvider;

    private const string VIEWS_FOLDER = 'resources/views';

    private ?LayerResourceNamespace $layerResourceNamespace = null;

    protected function injectWithViews(): void
    {
        $namespace = $this->resourceNamespace();

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (LayerProvider $provider): string => "\$this->withViews({$provider->pathExpression(self::VIEWS_FOLDER)}{$namespace->argument()});",
            self::VIEWS_FOLDER,
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

        $namespace = LayerResourceNamespace::of($this->resolveLayer()->manifest->name(), LayersCollection::fromConfig());

        if ($namespace->isExplicit()) {
            $this->components->warn("Another layer is also named [{$this->resolveLayer()->manifest->package()}]: this layer's views and components use the [{$namespace->name()}] namespace.");
        }

        return $this->layerResourceNamespace = $namespace;
    }
}
