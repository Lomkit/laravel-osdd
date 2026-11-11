<?php

namespace Lomkit\Osdd\Console;

use InvalidArgumentException;

class UnknownLayerGeneratorException extends InvalidArgumentException
{
    public function __construct(public readonly string $generator)
    {
        parent::__construct("Unknown generator [{$generator}]. Valid generators: ".implode(', ', LayerGenerator::names()).'.');
    }
}
