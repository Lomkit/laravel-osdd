<?php

namespace Lomkit\Osdd\Boundaries;

use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Finder\Finder;

final class LayerSourceFiles
{
    private const array SCANNED_FOLDERS = [LayerFolder::Database, LayerFolder::Routes, LayerFolder::Tests];

    private const array SKIPPED_DIRECTORIES = ['vendor', 'node_modules'];

    /**
     * @return array<string, bool> path => under the tests directory
     */
    public static function of(Layer $layer): array
    {
        $directories = array_values(array_filter(
            [$layer->sourceDirectory, ...array_map($layer->directory->path(...), self::SCANNED_FOLDERS)],
            is_dir(...),
        ));

        if ($directories === []) {
            return [];
        }

        $testsPrefix = $layer->directory->path(LayerFolder::Tests).'/';
        $files = [];

        foreach ((new Finder)->files()->name('*.php')->in(array_unique($directories))->exclude(self::SKIPPED_DIRECTORIES)->sortByName() as $file) {
            $path = (string) $file->getRealPath();
            $files[$path] = str_starts_with($path, $testsPrefix);
        }

        return $files;
    }
}
