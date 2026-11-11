<?php

namespace Lomkit\Osdd\Console\Exceptions;

use InvalidArgumentException;
use Lomkit\Osdd\Scaffolding\LayerGenerator;

class UnknownLayerGeneratorException extends InvalidArgumentException implements OsddConsoleException
{
    public function __construct(public readonly string $generator)
    {
        parent::__construct("Unknown generator [{$generator}]. Valid generators: ".implode(', ', LayerGenerator::names()).'.');
    }
}
