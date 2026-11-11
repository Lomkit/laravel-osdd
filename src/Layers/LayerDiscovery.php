<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class LayerDiscovery
{
    public function __construct(
        private readonly LayerBuckets $buckets,
        private readonly Filesystem $files,
    ) {}

    public function discover(): Layers
    {
        $layers = [];
        $invalidManifests = [];

        foreach ($this->buckets->all() as $path) {
            $this->scan($path, $layers, $invalidManifests);
        }

        return new Layers($layers, $invalidManifests);
    }

    /**
     * A directory with a composer.json is a package: a layer when its type is "layer", and never scanned deeper.
     *
     * @param  list<Layer>  $layers
     * @param  list<InvalidLayerManifestException>  $invalidManifests
     */
    private function scan(string $path, array &$layers, array &$invalidManifests): void
    {
        if (! $this->files->isDirectory($path)) {
            return;
        }

        foreach ((new Finder)->depth(0)->directories()->in($path)->sortByName() as $directory) {
            $root = (string) $directory->getRealPath();
            $manifest = $root.'/composer.json';

            if (! $this->files->isFile($manifest)) {
                $this->scan($root, $layers, $invalidManifests);

                continue;
            }

            try {
                $layer = LayerManifest::parse($this->files->get($manifest), $root);
            } catch (InvalidLayerManifestException $exception) {
                $invalidManifests[] = $exception;

                continue;
            }

            if ($layer !== null) {
                $layers[] = $layer;
            }
        }
    }
}
