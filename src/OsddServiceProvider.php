<?php

namespace Lomkit\Osdd;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Lomkit\Osdd\Composer\ComposerUpdate;
use Lomkit\Osdd\Composer\ProcessComposerUpdate;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Console\Commands\Make\MigrateMakeCommand;
use Lomkit\Osdd\Console\OsddAboutSection;
use Lomkit\Osdd\Console\OsddCommands;
use Lomkit\Osdd\Events\LayerEventServiceProvider;
use Lomkit\Osdd\IdeHelper\IdeHelperModelLocations;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerCache;
use Lomkit\Osdd\Layers\LayerDiscovery;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Layers\RefreshCacheAfterPackageDiscovery;
use Lomkit\Osdd\Resources\ResourceNamespaces;
use Lomkit\Osdd\Scaffolding\LayerScaffolds;
use Lomkit\Osdd\Seeders\AllRegisteredSeeders;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Seeders\SeederSelection;
use Lomkit\Osdd\Tinker\TinkerLayerAliases;

class OsddServiceProvider extends ServiceProvider
{
    private const string LAYER_CACHE_FILE = 'cache/osdd.php';

    private const string COMPOSER_BINARY_VARIABLE = 'COMPOSER_BINARY';

    private const string EVENT_DISCOVERY = 'osdd.events.discover';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(OsddCommands::available($this->app));
            $this->optimizes(optimize: 'osdd:cache', clear: 'osdd:clear');
            $this->app->make('events')->listen(CommandFinished::class, RefreshCacheAfterPackageDiscovery::class);
            AboutCommand::add('OSDD', OsddAboutSection::class);
        }

        $this->app->booted(function () {
            /** @var list<string> $argv */
            $argv = $_SERVER['argv'] ?? [];

            if (in_array('tinker', $argv)) {
                $this->app->make(TinkerLayerAliases::class)();
            }

            if (in_array('ide-helper:models', $argv)) {
                $this->app->make(IdeHelperModelLocations::class)();
            }
        });
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/osdd.php', 'osdd');

        $this->app->booting(function (Application $app): void {
            if ($app->make('config')->boolean(self::EVENT_DISCOVERY, false)) {
                $app->register(LayerEventServiceProvider::class);
            }
        });

        $this->app->singleton(LayerBuckets::class, fn (Application $app): LayerBuckets => LayerBuckets::fromConfig($app->make('config')));
        $this->app->singleton(LayerCache::class, fn (Application $app): LayerCache => new LayerCache(
            $app->make(Filesystem::class),
            $app->make(LayerBuckets::class),
            $app->bootstrapPath(self::LAYER_CACHE_FILE),
        ));
        $this->app->singleton(Layers::class, fn (Application $app): Layers => $app->make(LayerCache::class)->layers()
            ?? $app->make(LayerDiscovery::class)->discover());
        $this->app->singleton(SeederRegistry::class, fn () => new SeederRegistry);
        $this->app->singleton(ResourceNamespaces::class);
        $this->app->singleton(LayerScaffolds::class, fn (): LayerScaffolds => LayerScaffolds::standard());
        $this->app->bind(RootComposerJson::class, fn (Application $app): RootComposerJson => new RootComposerJson($app->make(Filesystem::class), $app->basePath()));
        $this->app->bind(ComposerUpdate::class, fn (Application $app): ComposerUpdate => new ProcessComposerUpdate(
            $app->make('composer'),
            $app->basePath(),
            getenv(self::COMPOSER_BINARY_VARIABLE) ?: null,
        ));
        $this->app->bind(SeederSelection::class, AllRegisteredSeeders::class);

        $this->app->singleton(MigrateMakeCommand::class, fn (Application $app): MigrateMakeCommand => new MigrateMakeCommand(
            $app->make('migration.creator'),
            $app->make('composer'),
        ));
    }
}
