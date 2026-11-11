<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Console\Events\CommandFinished;

final class RefreshCacheAfterPackageDiscovery
{
    private const string PACKAGE_DISCOVERY_COMMAND = 'package:discover';

    public function __construct(
        private readonly LayerCache $cache,
        private readonly RefreshLayers $refreshLayers,
    ) {}

    public function handle(CommandFinished $event): void
    {
        if ($event->command !== self::PACKAGE_DISCOVERY_COMMAND || $event->exitCode !== 0 || ! $this->cache->exists()) {
            return;
        }

        ($this->refreshLayers)();
    }
}
