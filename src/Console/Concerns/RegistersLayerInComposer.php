<?php

namespace Lomkit\Osdd\Console\Concerns;

use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Filesystem\Path;

trait RegistersLayerInComposer
{
    protected function registerLayerInComposer(string $name, string $layerPath): void
    {
        $composerPath = $this->laravel->basePath('composer.json');

        if (! $this->files->exists($composerPath)) {
            $this->components->warn('No composer.json found at project root, skipping Composer registration.');

            return;
        }

        $composer = ComposerJsonFile::open($this->files, $composerPath);
        $composer->addPathRepository($this->pathRepositoryUrl($layerPath));
        $composer->requirePackage($name, '*');
        $composer->save();

        $this->components->info("Registered <options=bold>{$name}</> in composer.json. Run <options=bold>composer update {$name}</> to install it.");
    }

    protected function injectProviderInComposerJson(string $composerPath, string $providerFqcn): void
    {
        $composer = ComposerJsonFile::open($this->files, $composerPath);
        $composer->addLaravelProvider($providerFqcn);
        $composer->save();
    }

    private function pathRepositoryUrl(string $layerPath): string
    {
        $bucket = Path::relative($this->laravel->basePath(), dirname($layerPath));

        if ($bucket === '') {
            return './*';
        }

        if (Path::isAbsolute($bucket) || $bucket === '..' || str_starts_with($bucket, '../')) {
            return $bucket.'/*';
        }

        return './'.$bucket.'/*';
    }
}
