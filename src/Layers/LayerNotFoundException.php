<?php

namespace Lomkit\Osdd\Layers;

use RuntimeException;

class LayerNotFoundException extends RuntimeException
{
    /**
     * @param  list<string>  $availableLayers
     */
    public function __construct(
        public readonly string $layerName,
        public readonly array $availableLayers,
        public readonly ?string $suggestion,
    ) {
        parent::__construct("Layer [{$layerName}] not found. ".$this->availability().$this->hint());
    }

    private function availability(): string
    {
        if ($this->availableLayers === []) {
            return 'No layer was discovered in the osdd.layers.paths directories.';
        }

        return 'Available layers: '.implode(', ', $this->availableLayers).'.';
    }

    private function hint(): string
    {
        return $this->suggestion === null ? '' : " Did you mean [{$this->suggestion}]?";
    }
}
