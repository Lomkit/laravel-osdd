<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

/**
 * @extends Collection<int, Layer>
 */
class LayersCollection extends Collection
{
    public static function fromConfig(): self
    {
        /** @var array<string, string> $paths */
        $paths = config('osdd.layers.paths', []);

        return self::discover(...array_values($paths));
    }

    public static function discover(string ...$paths): self
    {
        return new self(
            collect($paths)
                ->flatMap(fn (string $path) => self::scanPath($path))
                ->all()
        );
    }

    /**
     * @return list<Layer>
     */
    private static function scanPath(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $layers = [];

        foreach ((new Finder)->depth(0)->directories()->in($path) as $directory) {
            if (Layer::isValidLayerDirectory($directory)) {
                $layers[] = Layer::fromDirectory($directory);
            } else {
                array_push($layers, ...self::scanPath($directory->getRealPath()));
            }
        }

        return $layers;
    }
}
