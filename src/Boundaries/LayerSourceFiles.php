<?php

namespace Lomkit\Osdd\Boundaries;

use Lomkit\Osdd\Layers\Layer;
use Symfony\Component\Finder\Finder;

final class LayerSourceFiles
{
    private const array SCANNED_DIRECTORIES = ['database', 'routes', 'tests'];

    private const string TESTS_DIRECTORY = 'tests';

    private const array SKIPPED_DIRECTORIES = ['vendor', 'node_modules'];

    /**
     * @return array<string, bool> path => under the tests directory
     */
    public static function of(Layer $layer): array
    {
        $directories = array_values(array_filter(
            [$layer->manifest->srcPath($layer->path), ...array_map(fn (string $directory): string => $layer->path.'/'.$directory, self::SCANNED_DIRECTORIES)],
            is_dir(...),
        ));

        if ($directories === []) {
            return [];
        }

        $testsPrefix = rtrim($layer->path, '/').'/'.self::TESTS_DIRECTORY.'/';
        $files = [];

        foreach ((new Finder)->files()->name('*.php')->in(array_unique($directories))->exclude(self::SKIPPED_DIRECTORIES)->sortByName() as $file) {
            $path = (string) $file->getRealPath();
            $files[$path] = str_starts_with($path, $testsPrefix);
        }

        return $files;
    }
}
