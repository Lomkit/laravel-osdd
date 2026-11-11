<?php

namespace Lomkit\Osdd\Scaffolding;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\LayerComposerJson;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\RefreshLayers;
use Lomkit\Osdd\Stubs\Stub;
use Lomkit\Osdd\Stubs\StubWriter;

final readonly class DraftFiles
{
    public function __construct(
        private Filesystem $files,
        private StubWriter $stubs,
        private LayerComposerJson $composerJson,
        private RefreshLayers $refreshLayers,
        private LayerDirectory $directory,
    ) {}

    /**
     * @param  array<string, string>  $replacements
     */
    public function write(Stub $stub, string $relativePath, array $replacements = []): void
    {
        $this->stubs->write($stub, $this->directory->pathTo($relativePath), $replacements);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(Stub $stub, array $replacements = []): string
    {
        return $this->stubs->render($stub, $replacements);
    }

    public function put(string $relativePath, string $contents): void
    {
        $path = $this->directory->pathTo($relativePath);

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }

    public function hasMatching(LayerFolder $folder, string $pattern): bool
    {
        return $this->files->glob($this->directory->path($folder).'/'.$pattern) !== [];
    }

    /**
     * Declare the provider in the layer's extra.laravel.providers, then discover the layers again.
     */
    public function declareProvider(string $provider): void
    {
        $this->composerJson->declareProvider($this->directory->manifestFile(), $provider);

        ($this->refreshLayers)();
    }
}
