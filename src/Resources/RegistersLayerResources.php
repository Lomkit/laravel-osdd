<?php

namespace Lomkit\Osdd\Resources;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Layers\ProviderLayerNotFoundException;

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

    private function claimResourceNamespace(ResourceKind $kind, ?string $namespace): string
    {
        $namespace ??= $this->app->make(Layers::class)->owning(static::class)?->name->package
            ?? throw new ProviderLayerNotFoundException(static::class);

        $this->app->make(ResourceNamespaces::class)->claim($kind, $namespace, static::class);

        return $namespace;
    }
}
