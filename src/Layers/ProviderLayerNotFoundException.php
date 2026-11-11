<?php

namespace Lomkit\Osdd\Layers;

use RuntimeException;

class ProviderLayerNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $provider)
    {
        parent::__construct("No discovered layer owns [{$provider}]: its namespace is under no layer's autoload.psr-4 prefix. Pass the namespace or prefix explicitly, or move the provider into a layer under osdd.layers.paths.");
    }
}
