<?php

namespace Lomkit\Osdd\Resources;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\LivewireManager;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Layers\ProviderLayerNotFoundException;
use Lomkit\Osdd\Livewire\InstalledLivewire;
use Lomkit\Osdd\Livewire\LivewireLocation;

/**
 * @phpstan-require-extends ServiceProvider
 */
trait RegistersLayerResources
{
    /**
     * @throws ResourceNamespaceCollisionException
     * @throws ProviderLayerNotFoundException
     */
    protected function withViews(string $path, ?string $namespace = null): void
    {
        if (! is_dir($path)) {
            return;
        }

        $namespace = $this->claimResourceNamespace(ResourceKind::Views, $namespace);

        $this->loadViewsFrom($path, $namespace);
    }

    /**
     * @throws ResourceNamespaceCollisionException
     * @throws ProviderLayerNotFoundException
     */
    protected function withTranslations(string $path, ?string $namespace = null): void
    {
        if (! is_dir($path)) {
            return;
        }

        $namespace = $this->claimResourceNamespace(ResourceKind::Translations, $namespace);

        $this->loadTranslationsFrom($path, $namespace);
        $this->loadJsonTranslationsFrom($path);
    }

    /**
     * @throws ResourceNamespaceCollisionException
     * @throws ProviderLayerNotFoundException
     */
    protected function withComponents(string $namespace, ?string $prefix = null): void
    {
        $prefix = $this->claimResourceNamespace(ResourceKind::Components, $prefix);

        $this->callAfterResolving(
            BladeCompiler::class,
            fn (BladeCompiler $blade) => $blade->componentNamespace($namespace, $prefix),
        );
    }

    /**
     * Registers nothing unless Livewire 4 is installed and its provider registered.
     *
     * @throws ResourceNamespaceCollisionException
     * @throws ProviderLayerNotFoundException
     */
    protected function withLivewire(?string $namespace = null): void
    {
        if (! InstalledLivewire::runsIn($this->app)) {
            return;
        }

        $location = LivewireLocation::of($this->owningLayer(), $this->claimResourceNamespace(ResourceKind::Livewire, $namespace));

        $this->callAfterResolving(
            LivewireManager::class,
            fn (LivewireManager $livewire) => $location->registerIn($livewire),
        );
    }

    private function claimResourceNamespace(ResourceKind $kind, ?string $namespace): string
    {
        $namespace ??= $this->owningLayer()->name->package;

        $this->app->make(ResourceNamespaces::class)->claim($kind, $namespace, static::class);

        return $namespace;
    }

    private function owningLayer(): Layer
    {
        return $this->app->make(Layers::class)->owning(static::class)
            ?? throw new ProviderLayerNotFoundException(static::class);
    }
}
