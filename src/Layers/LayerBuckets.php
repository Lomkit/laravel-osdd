<?php

namespace Lomkit\Osdd\Layers;

final class LayerBuckets
{
    /**
     * @param  array<string, string>  $paths
     */
    private function __construct(private readonly array $paths) {}

    public static function fromConfig(): self
    {
        /** @var array<string, string> $paths */
        $paths = config('osdd.layers.paths', []);

        return new self($paths);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->paths);
    }

    public function path(string $bucket): string
    {
        return $this->paths[$bucket];
    }

    public function onlyBucket(): ?string
    {
        return count($this->paths) === 1 ? (string) array_key_first($this->paths) : null;
    }

    /**
     * The bucket named after the vendor, else the only bucket.
     */
    public function pathForVendor(string $vendor): ?string
    {
        $bucket = isset($this->paths[$vendor]) ? $vendor : $this->onlyBucket();

        return $bucket === null ? null : $this->paths[$bucket];
    }
}
