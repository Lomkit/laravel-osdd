<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Contracts\Container\Container;

final class RefreshLayers
{
    public function __construct(
        private readonly Container $container,
        private readonly LayerDiscovery $discovery,
        private readonly LayerCache $cache,
    ) {}

    /**
     * Discover the layers again for the rest of this process, and rewrite the layer cache when there is one.
     */
    public function __invoke(): Layers
    {
        $layers = $this->discovery->discover();

        $this->container->instance(Layers::class, $layers);

        if (! $this->cache->exists()) {
            return $layers;
        }

        if ($layers->invalidManifests() !== []) {
            $this->cache->clear();

            return $layers;
        }

        $this->cache->write($layers);

        return $layers;
    }
}
