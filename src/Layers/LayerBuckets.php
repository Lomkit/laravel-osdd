<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Contracts\Config\Repository;

final class LayerBuckets
{
    private const string PATHS_KEY = 'osdd.layers.paths';

    /**
     * @param  array<string, string>  $paths
     */
    public function __construct(private readonly array $paths) {}

    public static function fromConfig(Repository $config): self
    {
        $configured = $config->get(self::PATHS_KEY, []);
        $paths = [];

        foreach (is_array($configured) ? $configured : [] as $bucket => $path) {
            if (is_string($path)) {
                $paths[(string) $bucket] = $path;
            }
        }

        return new self($paths);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(strval(...), array_keys($this->paths));
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->paths;
    }

    public function has(string $bucket): bool
    {
        return isset($this->paths[$bucket]);
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
