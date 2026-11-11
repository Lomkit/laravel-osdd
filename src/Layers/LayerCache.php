<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Filesystem\Filesystem;

final class LayerCache
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly LayerBuckets $buckets,
        public readonly string $path,
    ) {}

    /**
     * The cached layers, or null when there is no cache or it was built for other layer paths.
     */
    public function layers(): ?Layers
    {
        if (! $this->exists()) {
            return null;
        }

        $cached = require $this->path;

        if (! is_array($cached) || ($cached['buckets'] ?? null) !== $this->buckets->all() || ! is_array($cached['layers'] ?? null)) {
            return null;
        }

        return $this->restore($cached['layers']);
    }

    /**
     * @throws InvalidLayerManifestException
     */
    public function write(Layers $layers): void
    {
        $layers->failOnInvalidManifest();

        $cached = [
            'buckets' => $this->buckets->all(),
            'layers' => $layers->map(fn (Layer $layer): array => ['directory' => $layer->directory->root, 'manifest' => $layer->toManifest()]),
        ];

        $this->files->ensureDirectoryExists(dirname($this->path));
        $this->files->replace($this->path, '<?php return '.var_export($cached, true).';'.PHP_EOL);
    }

    public function clear(): void
    {
        $this->files->delete($this->path);
    }

    public function exists(): bool
    {
        return $this->files->isFile($this->path);
    }

    /**
     * @param  array<array-key, mixed>  $entries
     */
    private function restore(array $entries): ?Layers
    {
        $layers = [];

        foreach ($entries as $entry) {
            if (! is_array($entry) || ! is_string($entry['directory'] ?? null) || ! is_array($entry['manifest'] ?? null)) {
                return null;
            }

            try {
                $layer = LayerManifest::fromData($entry['manifest'], $entry['directory']);
            } catch (InvalidLayerManifestException) {
                return null;
            }

            if ($layer === null) {
                return null;
            }

            $layers[] = $layer;
        }

        return new Layers($layers);
    }
}
