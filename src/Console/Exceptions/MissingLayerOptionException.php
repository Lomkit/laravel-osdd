<?php

namespace Lomkit\Osdd\Console\Exceptions;

use InvalidArgumentException;

class MissingLayerOptionException extends InvalidArgumentException implements OsddConsoleException
{
    /**
     * @param  list<string>  $availableLayers
     */
    public function __construct(public readonly array $availableLayers)
    {
        parent::__construct('The --layer option is required when the command is not interactive. Available layers: '.(implode(', ', $availableLayers) ?: 'none').'.');
    }
}
