<?php

namespace Lomkit\Osdd\Console\Concerns;

use stdClass;

trait RegistersLayerInComposer
{
    protected function registerLayerInComposer(string $name, string $layerPath): void
    {
        $composerPath = $this->laravel->basePath('composer.json');

        if (! $this->files->exists($composerPath)) {
            $this->components->warn('No composer.json found at project root, skipping Composer registration.');

            return;
        }

        $composer = $this->decodeComposerJson($composerPath);

        $relativePath = $this->relativeLayerPath($layerPath);

        $this->addPathRepository($composer, $relativePath);
        $this->addRequireEntry($composer, $name);
        $this->normalizeComposerPsr4($composer);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );

        $this->components->info("Registered <options=bold>{$name}</> in composer.json. Run <options=bold>composer update {$name}</> to install it.");
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeComposerJson(string $composerPath): array
    {
        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($composerPath), true, 512, JSON_THROW_ON_ERROR);

        return $composer;
    }

    private function relativeLayerPath(string $layerPath): string
    {
        $base = rtrim(str_replace('\\', '/', $this->laravel->basePath()), '/').'/';
        $layer = str_replace('\\', '/', $layerPath);

        return './'.ltrim(str_replace($base, '', $layer), '/');
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    private function addPathRepository(array &$composer, string $relativePath): void
    {
        $wildcardUrl = dirname($relativePath).'/*';

        /** @var array<array-key, array<string, mixed>> $repositories */
        $repositories = $composer['repositories'] ?? [];

        foreach ($repositories as $repo) {
            if (($repo['url'] ?? '') === $wildcardUrl) {
                return;
            }
        }

        $repositories[] = ['type' => 'path', 'url' => $wildcardUrl];
        $composer['repositories'] = $repositories;
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    private function addRequireEntry(array &$composer, string $name): void
    {
        /** @var array<string, string> $require */
        $require = $composer['require'] ?? [];

        if (! isset($require[$name])) {
            $require[$name] = '*';
            $composer['require'] = $require;
        }
    }

    protected function injectProviderInComposerJson(string $composerPath, string $providerFqcn): void
    {
        $composer = $this->decodeComposerJson($composerPath);

        /** @var array<string, mixed> $extra */
        $extra = $composer['extra'] ?? [];

        /** @var array<string, mixed> $laravel */
        $laravel = $extra['laravel'] ?? [];

        /** @var list<string> $providers */
        $providers = $laravel['providers'] ?? [];
        $providers[] = $providerFqcn;

        $laravel['providers'] = array_values(array_unique($providers));
        $extra['laravel'] = $laravel;
        $composer['extra'] = $extra;

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    private function normalizeComposerPsr4(array &$composer): void
    {
        foreach (['autoload', 'autoload-dev'] as $section) {
            $autoload = $composer[$section] ?? null;

            if (is_array($autoload) && isset($autoload['psr-4']) && empty($autoload['psr-4'])) {
                $autoload['psr-4'] = new stdClass;
                $composer[$section] = $autoload;
            }
        }
    }
}
