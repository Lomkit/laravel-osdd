<?php

namespace Lomkit\Osdd\Tests\Livewire;

use Livewire\LivewireServiceProvider;
use Lomkit\Osdd\OsddServiceProvider;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;

abstract class LivewireTestCase extends TestCase
{
    private const string APPLICATION_KEY = 'base64:T3NkZExpdmV3aXJlVGVzdEtleUZvckNoZWNrc3VtczA=';

    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', self::APPLICATION_KEY);
    }

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, OsddServiceProvider::class];
    }

    protected function layerPath(string $path): string
    {
        return $this->project()->path(LayerFixture::PROVIDER_LAYER.'/'.$path);
    }

    protected function write(string $path, string $contents): void
    {
        $this->app['files']->ensureDirectoryExists(dirname($this->layerPath($path)));
        $this->app['files']->put($this->layerPath($path), $contents);
    }
}
