<?php

namespace Lomkit\Osdd\Graph;

use Closure;
use Lomkit\Osdd\Layers\Layers;

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

    public static function of(Layers $layers): self
    {
        $names = $layers->names();
        $requires = [];
        $devRequires = [];

        foreach ($layers as $layer) {
            $requires[$layer->name->toString()] = self::layersAmong($layer->requires, $names);
            $devRequires[$layer->name->toString()] = self::layersAmong($layer->devRequires, $names);
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
     * The layers that list the layer in their require or require-dev, sorted by name.
     *
     * @return list<string>
     */
    public function dependentsOf(string $layer): array
    {
        return array_values(array_filter(
            $this->names(),
            fn (string $name): bool => in_array($layer, [...$this->requires($name), ...$this->devRequires($name)], true),
        ));
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
