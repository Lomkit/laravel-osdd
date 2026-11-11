<?php

namespace Lomkit\Osdd\Graph;

use Closure;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;

final class LayerGraph
{
    /**
     * @param  array<string, list<string>>  $requires
     * @param  array<string, list<string>>  $devRequires
     */
    private function __construct(
        private readonly array $requires,
        private readonly array $devRequires,
    ) {}

    public static function of(LayersCollection $layers): self
    {
        $names = $layers->names();
        $requires = [];
        $devRequires = [];

        foreach ($layers->sortBy(fn (Layer $layer): string => $layer->manifest->name()) as $layer) {
            $requires[$layer->manifest->name()] = self::layersAmong($layer->manifest->requires(), $names);
            $devRequires[$layer->manifest->name()] = self::layersAmong($layer->manifest->devRequires(), $names);
        }

        return new self($requires, $devRequires);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->requires);
    }

    /**
     * @return list<string>
     */
    public function requires(string $layer): array
    {
        return $this->requires[$layer] ?? [];
    }

    /**
     * @return list<string>
     */
    public function devRequires(string $layer): array
    {
        return $this->devRequires[$layer] ?? [];
    }

    /**
     * @param  list<string>  $layers
     * @return list<string>
     */
    public function reachableFrom(array $layers): array
    {
        $reached = [];
        $pending = $layers;

        while ($pending !== []) {
            $layer = array_pop($pending);

            if (! in_array($layer, $reached, true)) {
                $reached[] = $layer;
                array_push($pending, ...$this->requires($layer));
            }
        }

        sort($reached);

        return $reached;
    }

    /**
     * @param  Closure(string, string): bool  $keep
     */
    public function filterRequires(Closure $keep): self
    {
        $requires = [];

        foreach ($this->requires as $layer => $required) {
            $requires[$layer] = array_values(array_filter($required, fn (string $dependency): bool => $keep($layer, $dependency)));
        }

        return new self($requires, $this->devRequires);
    }

    /**
     * @param  list<string>  $packages
     * @param  list<string>  $layerNames
     * @return list<string>
     */
    private static function layersAmong(array $packages, array $layerNames): array
    {
        $layers = array_values(array_intersect($packages, $layerNames));
        sort($layers);

        return $layers;
    }
}
