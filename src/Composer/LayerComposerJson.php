<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Filesystem\Filesystem;

final readonly class LayerComposerJson
{
    public function __construct(private Filesystem $files) {}

    /**
     * A new layer gets the whole manifest; an existing composer.json keeps its content and only gains the keys it lacks.
     *
     * @throws MalformedComposerJsonException
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

    /**
     * @throws MalformedComposerJsonException
     */
    public function declareProvider(string $path, string $provider): void
    {
        $composer = ComposerJsonFile::open($this->files, $path);
        $composer->addLaravelProvider($provider);
        $composer->save();
    }
}
