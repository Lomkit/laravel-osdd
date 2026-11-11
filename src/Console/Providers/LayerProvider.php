<?php

namespace Lomkit\Osdd\Console\Providers;

use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Support\Path;

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
        foreach ($layer->manifest->providers() as $provider) {
            $path = $layer->manifest->classPath($layer->path, $provider);

            if ($path !== null && is_file($path)) {
                return new self($layer, $path);
            }
        }

        $conventional = Str::studly($layer->manifest->package()).'ServiceProvider.php';

        return new self($layer, $layer->manifest->srcPath($layer->path).'/Providers/'.$conventional);
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    public function pathExpression(string $layerRelativePath): string
    {
        $relative = Path::relative(dirname($this->path), $this->layer->path.'/'.$layerRelativePath);

        return "__DIR__ . '/{$relative}'";
    }
}
