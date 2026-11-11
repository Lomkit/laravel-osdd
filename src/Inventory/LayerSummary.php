<?php

namespace Lomkit\Osdd\Inventory;

use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;

final readonly class LayerSummary
{
    /**
     * @param  list<string>  $providers
     * @param  list<string>  $requires
     */
    private function __construct(
        public string $name,
        public string $path,
        public string $namespace,
        public array $providers,
        public array $requires,
        public LayerCounts $counts,
        public LayerHealth $health,
    ) {}

    public static function of(Layer $layer, LayersCollection $layers, RootProject $project): self
    {
        return new self(
            name: $layer->manifest->name(),
            path: $project->relativePath($layer->path),
            namespace: $layer->manifest->rootNamespace(),
            providers: $layer->manifest->providers(),
            requires: array_values(array_intersect($layer->manifest->requires(), $layers->names())),
            counts: LayerCounts::of($layer),
            health: LayerHealth::of($layer, $project),
        );
    }

    /**
     * @return array{name: string, path: string, namespace: string, providers: list<string>, requires: list<string>, counts: array<string, int>, health: array<string, bool>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'namespace' => $this->namespace,
            'providers' => $this->providers,
            'requires' => $this->requires,
            'counts' => $this->counts->toArray(),
            'health' => $this->health->toArray(),
        ];
    }
}
