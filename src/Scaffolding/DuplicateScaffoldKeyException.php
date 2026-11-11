<?php

namespace Lomkit\Osdd\Scaffolding;

use LogicException;

final class DuplicateScaffoldKeyException extends LogicException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct("Two scaffolds share the key [{$key}].");
    }
}
