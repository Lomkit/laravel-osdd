<?php

namespace Lomkit\Osdd\Removal;

use Lomkit\Osdd\Console\Exceptions\OsddConsoleException;
use RuntimeException;

final class LayerStillRequiredException extends RuntimeException implements OsddConsoleException
{
    /**
     * @param  list<string>  $dependents
     */
    public function __construct(public readonly string $layerName, public readonly array $dependents)
    {
        parent::__construct("Layer [{$layerName}] is required by ".implode(', ', $dependents).': remove it from their require or require-dev first.');
    }
}
