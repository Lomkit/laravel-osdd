<?php

namespace Lomkit\Osdd\Layers;

use RuntimeException;

class InvalidLayerManifestException extends RuntimeException
{
    public function __construct(public readonly string $manifestFile, public readonly string $reason)
    {
        parent::__construct("Invalid layer manifest [{$manifestFile}]: {$reason}");
    }
}
