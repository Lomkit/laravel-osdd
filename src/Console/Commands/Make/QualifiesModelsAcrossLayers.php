<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Layers\LayersCollection;

trait QualifiesModelsAcrossLayers
{
    /**
     * Keep a model that already names an existing class or another layer's namespace;
     * qualify a short name under the current layer's Models namespace.
     */
    #[\Override]
    protected function qualifyModel(string $model): string
    {
        $model = str_replace('/', '\\', ltrim($model, '\\/'));

        /** @var class-string $qualified */
        $qualified = class_exists($model) || $this->startsWithALayerNamespace($model)
            ? $model
            : $this->rootNamespace().'Models\\'.$model;

        return $qualified;
    }

    private function startsWithALayerNamespace(string $model): bool
    {
        foreach (LayersCollection::fromConfig() as $layer) {
            $namespace = $layer->manifest->rootNamespace();

            if ($namespace !== '' && str_starts_with($model, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
