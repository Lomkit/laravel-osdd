<?php

namespace Lomkit\Osdd\Scaffolding;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Composer\LayerComposerManifest;

final class LayerComposerJson
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * A new layer gets the whole manifest; an existing composer.json keeps its content and only gains the keys it lacks.
     */
    public function write(LayerComposerManifest $manifest, string $path): void
    {
        $this->files->ensureDirectoryExists(dirname($path));

        $composer = $this->files->exists($path)
            ? ComposerJsonFile::open($this->files, $path)
            : ComposerJsonFile::create($this->files, $path);

        $composer->addMissing($manifest->toObject());

        foreach ($manifest->requiredLayers as $requiredLayer) {
            $composer->requirePackage($requiredLayer, LayerComposerManifest::CONSTRAINT);
        }

        $composer->save();
    }
}
