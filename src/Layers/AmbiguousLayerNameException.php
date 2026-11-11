<?php

namespace Lomkit\Osdd\Layers;

use Lomkit\Osdd\Console\Exceptions\OsddConsoleException;
use RuntimeException;

class AmbiguousLayerNameException extends RuntimeException implements OsddConsoleException
{
    /**
     * @param  list<string>  $candidates
     */
    public function __construct(public readonly string $layerName, public readonly array $candidates)
    {
        parent::__construct("Layer [{$layerName}] is ambiguous: it matches ".implode(', ', $candidates).'. Pass the full layer name.');
    }
}
