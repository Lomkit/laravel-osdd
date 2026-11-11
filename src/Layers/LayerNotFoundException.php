<?php

namespace Lomkit\Osdd\Layers;

use RuntimeException;

class LayerNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $layerName)
    {
        parent::__construct("Layer '{$layerName}' not found.");
    }
}
