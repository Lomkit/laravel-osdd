<?php

namespace Lomkit\Osdd\Boundaries;

final class BoundaryIgnoreList
{
    /**
     * @param  list<string>  $classPrefixes
     * @param  list<array{string, string}>  $layerPairs
     */
    private function __construct(
        private readonly array $classPrefixes,
        private readonly array $layerPairs,
    ) {}

    /**
     * @throws InvalidBoundaryIgnoreEntryException
     */
    public static function fromConfig(): self
    {
        $entries = config('osdd.boundaries.ignore') ?? [];
        $classPrefixes = [];
        $layerPairs = [];

        foreach (is_array($entries) ? $entries : [$entries] as $entry) {
            if (is_string($entry) && $entry !== '') {
                $classPrefixes[] = ltrim($entry, '\\');
            } elseif (is_array($entry) && array_is_list($entry) && count($entry) === 2 && is_string($entry[0]) && is_string($entry[1])) {
                $layerPairs[] = [$entry[0], $entry[1]];
            } else {
                throw new InvalidBoundaryIgnoreEntryException($entry);
            }
        }

        return new self($classPrefixes, $layerPairs);
    }

    public function ignoresClass(string $name): bool
    {
        foreach ($this->classPrefixes as $prefix) {
            if ($name === rtrim($prefix, '\\') || str_starts_with($name, rtrim($prefix, '\\').'\\')) {
                return true;
            }
        }

        return false;
    }

    public function ignoresPair(string $layer, string $requirement): bool
    {
        return in_array([$layer, $requirement], $this->layerPairs, true);
    }
}
