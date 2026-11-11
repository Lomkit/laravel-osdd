<?php

namespace Lomkit\Osdd\Scaffolding;

use LogicException;

final class ScaffoldDependencyCycleException extends LogicException
{
    /**
     * @param  list<string>  $cycle
     */
    public function __construct(public readonly array $cycle)
    {
        parent::__construct('The scaffolds depend on each other: '.implode(' -> ', $cycle).'.');
    }
}
