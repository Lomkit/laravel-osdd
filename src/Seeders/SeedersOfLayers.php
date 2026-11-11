<?php

namespace Lomkit\Osdd\Seeders;

use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;

final class SeedersOfLayers implements SeederSelection
{
    /**
     * @param  list<string>  $selectedLayers
     */
    private function __construct(
        private readonly LayersCollection $layers,
        private readonly array $selectedLayers,
    ) {}

    public static function of(LayersCollection $selected, LayersCollection $layers): self
    {
        return new self($layers, $selected->names());
    }

    #[\Override]
    public function select(array $seeders): array
    {
        return array_values(array_filter($seeders, $this->belongsToSelectedLayer(...)));
    }

    #[\Override]
    public function nothingSelectedWarning(): string
    {
        return 'No OSDD seeders registered for '.implode(', ', $this->selectedLayers).'.';
    }

    private function belongsToSelectedLayer(string $seeder): bool
    {
        return in_array($this->owningLayer($seeder)?->manifest->name(), $this->selectedLayers, true);
    }

    private function owningLayer(string $seeder): ?Layer
    {
        $seeder = ltrim($seeder, '\\');
        $owner = null;
        $longestPrefix = 0;

        foreach ($this->layers as $layer) {
            foreach ($layer->manifest->namespaces() as $namespace) {
                if (str_starts_with($seeder, $namespace) && strlen($namespace) > $longestPrefix) {
                    $owner = $layer;
                    $longestPrefix = strlen($namespace);
                }
            }
        }

        return $owner;
    }
}
