<?php

namespace Lomkit\Osdd\Composer;

use RuntimeException;

class InvalidComposerJsonException extends RuntimeException
{
    public function __construct(public readonly string $path, public readonly string $key)
    {
        parent::__construct("Cannot edit [{$path}]: \"{$key}\" must be a JSON object.");
    }
}
