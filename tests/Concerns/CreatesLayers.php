<?php

namespace Lomkit\Osdd\Tests\Concerns;

use Illuminate\Support\Str;

trait CreatesLayers
{
    /**
     * @var list<string>
     */
    private array $createdLayerPaths = [];

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function createLayer(string $directory, string $name, array $manifest = []): string
    {
        $this->createdLayerPaths[] = $directory;

        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put($directory.'/composer.json', json_encode(
            ['name' => $name, 'type' => 'layer', ...$manifest],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));

        return $directory;
    }

    /**
     * Write a provider extending LayerServiceProvider under a unique class name in the layer's src/Providers.
     *
     * @return array{class: class-string, path: string}
     */
    private function createLayerProvider(string $layerPath, string $namespace, string $boot): array
    {
        $short = 'Provider'.Str::random(12);
        $path = $layerPath.'/src/Providers/'.$short.'.php';

        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, "<?php\n\nnamespace {$namespace}\\Providers;\n\nuse Lomkit\\Osdd\\LayerServiceProvider;\n\nclass {$short} extends LayerServiceProvider\n{\n    public function boot(): void\n    {\n{$boot}\n    }\n}\n");

        return ['class' => $namespace.'\\Providers\\'.$short, 'path' => $path];
    }

    /**
     * @param  array{class: class-string, path: string}  $provider
     */
    private function registerLayerProvider(array $provider): void
    {
        require_once $provider['path'];

        $this->app->register($provider['class']);
    }

    private function deleteCreatedLayers(): void
    {
        foreach ($this->createdLayerPaths as $path) {
            $this->app['files']->deleteDirectory($path);
        }

        $this->createdLayerPaths = [];
    }
}
