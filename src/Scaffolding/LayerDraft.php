<?php

namespace Lomkit\Osdd\Scaffolding;

use Illuminate\Support\Str;
use Lomkit\Osdd\Composer\LayerComposerManifest;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;

final readonly class LayerDraft
{
    public LayerName $name;

    public function __construct(
        public LayerDirectory $directory,
        public LayerComposerManifest $manifest,
    ) {
        $this->name = $manifest->name;
    }

    /**
     * A layer named after its package, inside the given bucket directory.
     */
    public static function inBucket(string $bucketPath, LayerComposerManifest $manifest): self
    {
        return new self(new LayerDirectory($bucketPath.'/'.$manifest->name->package), $manifest);
    }

    public function studly(): string
    {
        return $this->name->studlyPackage();
    }

    public function singular(): string
    {
        return Str::singular($this->studly());
    }

    /**
     * The path of a file under the layer source directory, relative to the layer root.
     */
    public function sourceFile(string $relativePath): string
    {
        return LayerComposerManifest::SOURCE_DIRECTORY.'/'.$relativePath;
    }
}
