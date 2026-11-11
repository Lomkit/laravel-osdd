<?php

namespace Lomkit\Osdd\Layers;

use RuntimeException;

class LayerManifestNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $provider, public readonly string $providerFile)
    {
        parent::__construct("No composer.json found above [{$providerFile}], so [{$provider}] has no layer package name. Pass the namespace or prefix explicitly.");
    }
}
