<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;

final class RootComposerJson
{
    private const string FILE = 'composer.json';

    private const string LAYER_CONSTRAINT = '*';

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $basePath,
    ) {}

    public function exists(): bool
    {
        return $this->files->exists($this->path());
    }

    /**
     * Add a path repository covering the layer's bucket and require the layer.
     */
    public function requireLayer(LayerName $name, LayerDirectory $directory): void
    {
        $composer = ComposerJsonFile::open($this->files, $this->path());
        $composer->addPathRepository($this->pathRepositoryUrl($directory));
        $composer->requirePackage($name->toString(), self::LAYER_CONSTRAINT);
        $composer->save();
    }

    private function path(): string
    {
        return $this->basePath.'/'.self::FILE;
    }

    private function pathRepositoryUrl(LayerDirectory $directory): string
    {
        $bucket = Path::relative($this->basePath, dirname($directory->root));

        if ($bucket === '') {
            return './*';
        }

        if (Path::isAbsolute($bucket) || $bucket === '..' || str_starts_with($bucket, '../')) {
            return $bucket.'/*';
        }

        return './'.$bucket.'/*';
    }
}
