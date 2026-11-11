<?php

namespace Lomkit\Osdd\Layers;

use InvalidArgumentException;

class LayerRequiresItselfException extends InvalidArgumentException
{
    public function __construct(public readonly string $layerName)
    {
        parent::__construct("Layer [{$layerName}] cannot require itself.");
    }
}
