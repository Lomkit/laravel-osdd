<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;

final readonly class RootComposerJson
{
    private const string FILE = 'composer.json';

    public function __construct(
        private Filesystem $files,
        public string $basePath,
    ) {}

    public function exists(): bool
    {
        return $this->files->exists($this->path());
    }

    /**
     * Add a path repository covering the layer's bucket and require the layer.
     *
     * @throws MalformedComposerJsonException
     */
    public function requireLayer(LayerName $name, LayerDirectory $directory): void
    {
        $composer = $this->open();
        $composer->addPathRepository(PathRepository::forLayerDirectory($this->basePath, $directory));
        $composer->requirePackage($name->toString(), LayerComposerManifest::CONSTRAINT);
        $composer->save();
    }

    /**
     * @throws MalformedComposerJsonException
     */
    public function removeAutoloadNamespaces(string ...$namespaces): void
    {
        $composer = $this->open();

        foreach ($namespaces as $namespace) {
            $composer->removeAutoloadNamespace($namespace);
        }

        $composer->save();
    }

    /**
     * @return array<string, string>
     *
     * @throws MalformedComposerJsonException
     */
    public function psr4(): array
    {
        return $this->exists() ? $this->open()->psr4() : [];
    }

    /**
     * @return list<string>
     *
     * @throws MalformedComposerJsonException
     */
    public function requiredPackages(): array
    {
        return $this->exists() ? $this->open()->requiredPackages() : [];
    }

    /**
     * @return list<PathRepository>
     *
     * @throws MalformedComposerJsonException
     */
    public function pathRepositories(): array
    {
        return $this->exists() ? $this->open()->pathRepositories() : [];
    }

    private function open(): ComposerJsonFile
    {
        return ComposerJsonFile::open($this->files, $this->path());
    }

    private function path(): string
    {
        return $this->basePath.'/'.self::FILE;
    }
}
