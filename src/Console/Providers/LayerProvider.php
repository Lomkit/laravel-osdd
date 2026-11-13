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
     * The first provider of extra.laravel.providers found in the layer that extends LayerServiceProvider, else the
     * conventional <source>/Providers/<Package>ServiceProvider.php when it does; null when the layer has none.
     */
    public static function of(Layer $layer): ?self
    {
        foreach (self::candidates($layer) as $class => $path) {
            if (is_file($path) && (new ProviderClass($class, $path))->extendsLayerServiceProvider()) {
                return new self($layer->directory, $path);
            }
        }

        return null;
    }

    public static function conventional(Layer $layer): self
    {
        return new self($layer->directory, $layer->sourceDirectory.'/'.(new ConventionalProvider($layer->name))->sourceFile());
    }

    public static function at(LayerDirectory $layerDirectory, string $path): self
    {
        return new self($layerDirectory, $path);
    }

    public function pathExpression(string $layerRelativePath): string
    {
        $relative = Path::relative(dirname($this->path), $this->layerDirectory->pathTo($layerRelativePath));

        return "__DIR__ . '/{$relative}'";
    }

    /**
     * @return array<string, string> class => file
     */
    private static function candidates(Layer $layer): array
    {
        $candidates = [];

        foreach ($layer->providers as $provider) {
            $path = $layer->classFile($provider);

            if ($path !== null) {
                $candidates[ltrim($provider, '\\')] ??= $path;
            }
        }

        $conventional = new ConventionalProvider($layer->name);
        $candidates[$conventional->classIn($layer->rootNamespace)] ??= self::conventional($layer)->path;

        return $candidates;
    }
}
