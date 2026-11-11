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
     * @throws LayerNotFoundException
     * @throws AmbiguousLayerNameException
     */
    public function findByName(string $name): Layer
    {
        $layer = $this->first(fn (Layer $layer): bool => $layer->manifest->name() === $name);

        if ($layer !== null) {
            return $layer;
        }

        $packageMatches = $this->filter(fn (Layer $layer): bool => $layer->manifest->package() === $name);

        return match ($packageMatches->count()) {
            0 => throw new LayerNotFoundException($name, $this->names(), $this->closestName($name)),
            1 => $packageMatches->sole(),
            default => throw new AmbiguousLayerNameException($name, $packageMatches->names()),
        };
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_values($this->toBase()->map(fn (Layer $layer): string => $layer->manifest->name())->sort()->all());
    }

    private function closestName(string $name): ?string
    {
        $maximumDistance = max(1, intdiv(strlen($name), 3));
        $closest = null;

        foreach ($this as $layer) {
            $distance = min(levenshtein($name, $layer->manifest->name()), levenshtein($name, $layer->manifest->package()));

            if ($distance <= $maximumDistance) {
                $closest = $layer->manifest->name();
                $maximumDistance = $distance - 1;
            }
        }

        return $closest;
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
