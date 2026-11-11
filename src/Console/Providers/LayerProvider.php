<?php

namespace Lomkit\Osdd\Console\Providers;

use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerDirectory;

final class LayerProvider
{
    private function __construct(
        private readonly LayerDirectory $layerDirectory,
        public readonly string $path,
    ) {}

    /**
     * The first provider of extra.laravel.providers found in the layer, else
     * the conventional <source>/Providers/<Package>ServiceProvider.php.
     */
    public static function of(Layer $layer): self
    {
        foreach ($layer->providers as $provider) {
            $path = $layer->classFile($provider);

            if ($path !== null && is_file($path)) {
                return new self($layer->directory, $path);
            }
        }

        return new self($layer->directory, $layer->sourceDirectory.'/'.(new ConventionalProvider($layer->name))->sourceFile());
    }

    public static function at(LayerDirectory $layerDirectory, string $path): self
    {
        return new self($layerDirectory, $path);
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function pathExpression(string $layerRelativePath): string
    {
        $relative = Path::relative(dirname($this->path), $this->layerDirectory->pathTo($layerRelativePath));

        return "__DIR__ . '/{$relative}'";
    }
}
