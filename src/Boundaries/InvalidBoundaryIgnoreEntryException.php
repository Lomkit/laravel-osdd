<?php

namespace Lomkit\Osdd\Boundaries;

use InvalidArgumentException;
use Lomkit\Osdd\Console\Exceptions\OsddConsoleException;

class InvalidBoundaryIgnoreEntryException extends InvalidArgumentException implements OsddConsoleException
{
    public function __construct(public readonly mixed $entry)
    {
        parent::__construct('Invalid osdd.boundaries.ignore entry ['.json_encode($entry).']. Use a class prefix such as \'Technical\\Shared\\Contracts\\\' or a layer pair such as [\'functional/orders\', \'technical/payments\'].');
    }
}
