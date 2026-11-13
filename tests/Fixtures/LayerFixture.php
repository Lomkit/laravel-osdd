<?php

namespace Lomkit\Osdd\Tests\Fixtures;

use Illuminate\Foundation\Application;
use Lomkit\Osdd\LayerServiceProvider;

final class LayerFixture
{
    public const string PROVIDER_LAYER = 'functional/provider-layer';

    private static int $providers = 0;

    public function __construct(private readonly Application $app) {}

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function layer(string $name, array $manifest = []): string
    {
        return $this->layerAt($this->app->basePath($name), $name, $manifest);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public function layerAt(string $directory, string $name, array $manifest = []): string
    {
        $this->app['files']->ensureDirectoryExists($directory);
        $this->app['files']->put($directory.'/composer.json', (string) json_encode(
            ['name' => $name, 'type' => 'layer', ...$manifest],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));

        return $directory;
    }

    public function bucket(string $vendor): string
    {
        $path = $this->app->basePath($vendor);
        $this->app['config']->set('osdd.layers.paths.'.$vendor, $path);

        return $path;
    }

    /**
     * Write a provider extending LayerServiceProvider, or the given parent, under a name no other test of the process uses.
     */
    public function provider(string $layerPath, string $namespace, string $methods, string $folder = 'Providers', string $parent = LayerServiceProvider::class): ProviderFile
    {
        $short = 'FixtureServiceProvider'.++self::$providers;
        $classNamespace = $namespace.'\\'.str_replace('/', '\\', $folder);
        $path = $layerPath.'/src/'.$folder.'/'.$short.'.php';
        $parentShort = class_basename($parent);

        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, "<?php\n\nnamespace {$classNamespace};\n\nuse {$parent};\n\nclass {$short} extends {$parentShort}\n{\n{$methods}\n}\n");

        return new ProviderFile($classNamespace.'\\'.$short, $path);
    }

    /**
     * Create functional/provider-layer with a provider declared in its extra.laravel.providers.
     */
    public function providerLayer(string $methods, string $folder = 'Providers'): ProviderFile
    {
        $layerPath = $this->app->basePath(self::PROVIDER_LAYER);
        $provider = $this->provider($layerPath, 'Functional\\ProviderLayer', $methods, $folder);

        $this->layerAt($layerPath, self::PROVIDER_LAYER, [
            'autoload' => ['psr-4' => ['Functional\\ProviderLayer\\' => 'src/']],
            'extra' => ['laravel' => ['providers' => [$provider->class]]],
        ]);

        return $provider;
    }

    public static function bootMethod(string $body = '//'): string
    {
        return "    public function boot(): void\n    {\n        {$body}\n    }";
    }
}
