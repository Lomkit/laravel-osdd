<?php

namespace Lomkit\Osdd\Layers;

use Lomkit\Osdd\Filesystem\Path;

final readonly class LayerDirectory
{
    private const string MANIFEST_FILE = 'composer.json';

    public string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/\\');
    }

    public function path(LayerFolder $folder): string
    {
        return $this->pathTo($folder->value);
    }

    public function pathTo(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/\\');

        return $relativePath === '' ? $this->root : $this->root.'/'.$relativePath;
    }

    public function manifestFile(): string
    {
        return $this->pathTo(self::MANIFEST_FILE);
    }

    public function relativeTo(string $base): string
    {
        return Path::relative($base, $this->root);
    }
}
