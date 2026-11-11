<?php

namespace Lomkit\Osdd\Layers;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Layer>
 */
final class Layers implements Countable, IteratorAggregate
{
    /**
     * @var list<Layer>
     */
    private readonly array $layers;

    /**
     * @param  list<Layer>  $layers
     * @param  list<InvalidLayerManifestException>  $invalidManifests
     */
    public function __construct(array $layers, private readonly array $invalidManifests = [])
    {
        usort($layers, fn (Layer $a, Layer $b): int => strcmp($a->name->toString(), $b->name->toString()));

        $this->layers = $layers;
    }

    /**
     * @throws LayerNotFoundException
     * @throws AmbiguousLayerNameException
     */
    public function find(string $name): Layer
    {
        foreach ($this->layers as $layer) {
            if ($layer->name->toString() === $name) {
                return $layer;
            }
        }

        $packageMatches = $this->filter(fn (Layer $layer): bool => $layer->name->package === $name);

        return match ($packageMatches->count()) {
            0 => throw new LayerNotFoundException($name, $this->names(), $this->closestName($name)),
            1 => $packageMatches->layers[0],
            default => throw new AmbiguousLayerNameException($name, $packageMatches->names()),
        };
    }

    /**
     * @param  list<string>  $names
     *
     * @throws LayerNotFoundException
     * @throws AmbiguousLayerNameException
     */
    public function findAll(array $names): self
    {
        $found = [];

        foreach ($names as $name) {
            $layer = $this->find($name);
            $found[$layer->name->toString()] = $layer;
        }

        return new self(array_values($found));
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->map(fn (Layer $layer): string => $layer->name->toString());
    }

    /**
     * The layer whose psr-4 prefix is the longest one the class starts with.
     */
    public function owning(string $class): ?Layer
    {
        $owner = null;
        $longest = 0;

        foreach ($this->layers as $layer) {
            if (($length = $layer->ownershipOf($class)) > $longest) {
                $owner = $layer;
                $longest = $length;
            }
        }

        return $owner;
    }

    /**
     * The other layers whose package part is the same as this name's.
     */
    public function sharingPackage(LayerName $name): self
    {
        return $this->filter(
            fn (Layer $layer): bool => $layer->name->package === $name->package && $layer->name->toString() !== $name->toString(),
        );
    }

    /**
     * @return list<string>
     */
    public function paths(LayerFolder $folder): array
    {
        return $this->map(fn (Layer $layer): string => $layer->directory->path($folder));
    }

    /**
     * @param  Closure(Layer): bool  $keep
     */
    public function filter(Closure $keep): self
    {
        return new self(array_values(array_filter($this->layers, $keep)), $this->invalidManifests);
    }

    /**
     * @template TResult
     *
     * @param  Closure(Layer): TResult  $callback
     * @return list<TResult>
     */
    public function map(Closure $callback): array
    {
        return array_map($callback, $this->layers);
    }

    public function isEmpty(): bool
    {
        return $this->layers === [];
    }

    #[\Override]
    public function count(): int
    {
        return count($this->layers);
    }

    /**
     * @return Traversable<int, Layer>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->layers);
    }

    /**
     * @return list<InvalidLayerManifestException>
     */
    public function invalidManifests(): array
    {
        return $this->invalidManifests;
    }

    /**
     * @throws InvalidLayerManifestException
     */
    public function failOnInvalidManifest(): self
    {
        if ($this->invalidManifests !== []) {
            throw $this->invalidManifests[0];
        }

        return $this;
    }

    private function closestName(string $name): ?string
    {
        $maximumDistance = max(1, intdiv(strlen($name), 3));
        $closest = null;

        foreach ($this->layers as $layer) {
            $distance = min(levenshtein($name, $layer->name->toString()), levenshtein($name, $layer->name->package));

            if ($distance <= $maximumDistance) {
                $closest = $layer->name->toString();
                $maximumDistance = $distance - 1;
            }
        }

        return $closest;
    }
}
