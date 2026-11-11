<?php

namespace Lomkit\Osdd\Layers;

use InvalidArgumentException;
use Lomkit\Osdd\Console\Exceptions\OsddConsoleException;

class LayerRequiresItselfException extends InvalidArgumentException implements OsddConsoleException
{
    public function __construct(public readonly string $layerName)
    {
        parent::__construct("Layer [{$layerName}] cannot require itself.");
    }
}
