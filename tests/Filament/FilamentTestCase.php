<?php

namespace Lomkit\Osdd\Tests\Filament;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Lomkit\Osdd\OsddServiceProvider;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;
use Lomkit\Osdd\Tests\TestCase;

abstract class FilamentTestCase extends TestCase
{
    #[\Override]
    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::withoutLayers();
    }

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            OsddServiceProvider::class,
        ];
    }

    protected function layerPath(string $path): string
    {
        return $this->project()->path(LayerFixture::PROVIDER_LAYER.'/'.$path);
    }

    protected function writeClass(string $path, string $namespace, string $class, string $declaration): void
    {
        $this->app['files']->ensureDirectoryExists(dirname($this->layerPath($path)));
        $this->app['files']->put($this->layerPath($path), "<?php\n\nnamespace {$namespace};\n\n{$declaration}\n");
        require $this->layerPath($path);
    }
}
