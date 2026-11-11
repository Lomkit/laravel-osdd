<?php

namespace Lomkit\Osdd\Seeders;

use Lomkit\Osdd\Layers\Layers;

final class SeedersOfLayers implements SeederSelection
{
    /**
     * @param  list<string>  $selectedLayers
     */
    private function __construct(
        private readonly Layers $layers,
        private readonly array $selectedLayers,
    ) {}

    public static function of(Layers $selected, Layers $layers): self
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
        return in_array($this->layers->owning($seeder)?->name->toString(), $this->selectedLayers, true);
    }
}
