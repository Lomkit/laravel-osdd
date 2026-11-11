<?php

namespace Lomkit\Osdd\Boundaries;

use InvalidArgumentException;

class InvalidBoundaryIgnoreEntryException extends InvalidArgumentException
{
    public function __construct(public readonly mixed $entry)
    {
        parent::__construct('Invalid osdd.boundaries.ignore entry ['.json_encode($entry).']. Use a class prefix such as \'Technical\\Shared\\Contracts\\\' or a layer pair such as [\'functional/orders\', \'technical/payments\'].');
    }
}
