<?php

namespace Lomkit\Osdd\Console\Providers;

use Illuminate\Support\Str;
use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\Layer;

final class LayerProvider
{
    private function __construct(
        private readonly Layer $layer,
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
                return new self($layer, $path);
            }
        }

        $conventional = Str::studly($layer->name->package).'ServiceProvider.php';

        return new self($layer, $layer->sourceDirectory.'/Providers/'.$conventional);
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function pathExpression(string $layerRelativePath): string
    {
        $relative = Path::relative(dirname($this->path), $this->layer->directory->pathTo($layerRelativePath));

        return "__DIR__ . '/{$relative}'";
    }
}
