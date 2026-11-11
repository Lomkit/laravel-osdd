<?php

namespace Lomkit\Osdd\Tests\Concerns;

trait CreatesProviderLayer
{
    private const string PROVIDER_LAYER = 'functional/provider-layer';

    /**
     * Create functional/provider-layer whose provider is declared in extra.laravel.providers
     * under a unique class name, so each test can load its own version.
     *
     * @return array{class: class-string, path: string}
     */
    private function createProviderLayer(string $methods, string $directory = 'Providers'): array
    {
        $layerPath = $this->app->basePath(self::PROVIDER_LAYER);
        $short = 'Provider'.str_replace('.', '', uniqid('', true));
        $namespace = 'Functional\\ProviderLayer\\'.str_replace('/', '\\', $directory);
        $path = $layerPath.'/src/'.$directory.'/'.$short.'.php';

        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($layerPath.'/composer.json', json_encode([
            'name' => self::PROVIDER_LAYER,
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\ProviderLayer\\' => 'src/']],
            'extra' => ['laravel' => ['providers' => [$namespace.'\\'.$short]]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->app['files']->put($path, "<?php\n\nnamespace {$namespace};\n\nuse Lomkit\\Osdd\\LayerServiceProvider;\n\nclass {$short} extends LayerServiceProvider\n{\n{$methods}\n}\n");

        return ['class' => $namespace.'\\'.$short, 'path' => $path];
    }

    private function deleteProviderLayer(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath(self::PROVIDER_LAYER));
    }
}
