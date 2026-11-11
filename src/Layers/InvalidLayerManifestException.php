<?php

namespace Lomkit\Osdd\Layers;

use Lomkit\Osdd\Console\Exceptions\OsddConsoleException;
use RuntimeException;

class InvalidLayerManifestException extends RuntimeException implements OsddConsoleException
{
    public function __construct(public readonly string $manifestFile, public readonly string $reason)
    {
        parent::__construct("Invalid layer manifest [{$manifestFile}]: {$reason}");
    }
}
