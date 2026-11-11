<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\LayerCache;
use Lomkit\Osdd\Layers\LayerDiscovery;
use Lomkit\Osdd\Layers\Layers;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:cache')]
final class CacheCommand extends Command
{
    protected $signature = 'osdd:cache';

    protected $description = 'Discover the OSDD layers and cache them for faster boots';

    public function handle(LayerDiscovery $discovery, LayerCache $cache): int
    {
        $cache->clear();

        $layers = $discovery->discover();

        try {
            $cache->write($layers);
        } catch (InvalidLayerManifestException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->laravel->instance(Layers::class, $layers);

        $this->components->info(count($layers).' '.str('layer')->plural(count($layers)).' cached successfully.');

        return self::SUCCESS;
    }
}
