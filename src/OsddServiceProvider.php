<?php

namespace Lomkit\Osdd;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Lomkit\Osdd\Console\Commands\CacheCommand;
use Lomkit\Osdd\Console\Commands\CheckCommand;
use Lomkit\Osdd\Console\Commands\ClearCommand;
use Lomkit\Osdd\Console\Commands\GraphCommand;
use Lomkit\Osdd\Console\Commands\LayerCommand;
use Lomkit\Osdd\Console\Commands\ListCommand;
use Lomkit\Osdd\Console\Commands\Make\CacheTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\CastMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ChannelMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ClassMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ComponentMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ConfigMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ConsoleMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ControllerMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\EnumMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\EventMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ExceptionMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\FactoryMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\InterfaceMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\JobMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\JobMiddlewareMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ListenerMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MailMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MiddlewareMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MigrateMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ModelMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\NotificationMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\NotificationsTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ObserverMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\PolicyMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\QueueBatchesTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\QueueFailedTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\QueueTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\RequestMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ResourceMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\RuleMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ScopeMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\SeederMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ServiceProviderMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\SessionTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\TestMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\TraitMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ViewMakeCommand;
use Lomkit\Osdd\Console\Commands\PhpunitCommand;
use Lomkit\Osdd\Console\Commands\SeedCommand;
use Lomkit\Osdd\Console\Commands\StartCommand;
use Lomkit\Osdd\Console\OsddAboutSection;
use Lomkit\Osdd\IdeHelper\IdeHelperModelLocations;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerCache;
use Lomkit\Osdd\Layers\LayerDiscovery;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Layers\RefreshCacheAfterPackageDiscovery;
use Lomkit\Osdd\Resources\ResourceNamespaces;
use Lomkit\Osdd\Seeders\AllRegisteredSeeders;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Seeders\SeederSelection;
use Lomkit\Osdd\Tinker\TinkerLayerAliases;

class OsddServiceProvider extends ServiceProvider
{
    private const string LAYER_CACHE_FILE = 'cache/osdd.php';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
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
        $this->app->bind(SeederSelection::class, AllRegisteredSeeders::class);

        $this->app->singleton(MigrateMakeCommand::class, fn (Application $app): MigrateMakeCommand => new MigrateMakeCommand(
            $app->make('migration.creator'),
            $app->make('composer'),
        ));
    }

    private function registerCommands(): void
    {
        $this->commands([
            LayerCommand::class,
            CacheCommand::class,
            ClearCommand::class,
            ListCommand::class,
            CheckCommand::class,
            GraphCommand::class,
            StartCommand::class,
            PhpunitCommand::class,
            SeedCommand::class,
            CacheTableMakeCommand::class,
            CastMakeCommand::class,
            ChannelMakeCommand::class,
            ClassMakeCommand::class,
            ComponentMakeCommand::class,
            ConfigMakeCommand::class,
            ConsoleMakeCommand::class,
            ControllerMakeCommand::class,
            EnumMakeCommand::class,
            EventMakeCommand::class,
            ExceptionMakeCommand::class,
            FactoryMakeCommand::class,
            InterfaceMakeCommand::class,
            JobMakeCommand::class,
            JobMiddlewareMakeCommand::class,
            ListenerMakeCommand::class,
            MailMakeCommand::class,
            MiddlewareMakeCommand::class,
            MigrateMakeCommand::class,
            ModelMakeCommand::class,
            NotificationMakeCommand::class,
            NotificationsTableMakeCommand::class,
            ObserverMakeCommand::class,
            PolicyMakeCommand::class,
            QueueBatchesTableMakeCommand::class,
            QueueFailedTableMakeCommand::class,
            QueueTableMakeCommand::class,
            RequestMakeCommand::class,
            ResourceMakeCommand::class,
            RuleMakeCommand::class,
            ScopeMakeCommand::class,
            SeederMakeCommand::class,
            ServiceProviderMakeCommand::class,
            SessionTableMakeCommand::class,
            TestMakeCommand::class,
            TraitMakeCommand::class,
            ViewMakeCommand::class,
        ]);
    }
}
