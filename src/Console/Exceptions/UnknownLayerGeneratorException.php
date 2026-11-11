<?php

namespace Lomkit\Osdd\Console\Exceptions;

use InvalidArgumentException;

class UnknownLayerGeneratorException extends InvalidArgumentException implements OsddConsoleException
{
    /**
     * @param  list<string>  $validGenerators
     */
    public function __construct(public readonly string $generator, array $validGenerators)
    {
        parent::__construct("Unknown generator [{$generator}]. Valid generators: ".implode(', ', $validGenerators).'.');
    }
}
