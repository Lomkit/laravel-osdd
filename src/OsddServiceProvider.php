<?php

namespace Lomkit\Osdd;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Lomkit\Osdd\Console\Commands\LayerCommand;
use Lomkit\Osdd\Console\Commands\Make\CastMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ChannelMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ClassMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ConfigMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ConsoleMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ControllerMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\EnumMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\EventMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ExceptionMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\FactoryMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\InterfaceMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\JobMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ListenerMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MailMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MiddlewareMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MigrateMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ModelMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\NotificationMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ObserverMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\PolicyMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\RequestMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ResourceMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\RuleMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ScopeMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\SeederMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ServiceProviderMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\TestMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\TraitMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ViewMakeCommand;
use Lomkit\Osdd\Console\Commands\PhpunitCommand;
use Lomkit\Osdd\Console\Commands\SeedCommand;
use Lomkit\Osdd\Console\Commands\StartCommand;
use Lomkit\Osdd\Layers\LayersCollection;
use Symfony\Component\Finder\Finder;

class OsddServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        $this->publishConfig();

        $this->app->booted(function () {
            /** @var list<string> $argv */
            $argv = $_SERVER['argv'] ?? [];

            if (in_array('tinker', $argv)) {
                $this->registerTinkerAliases();
            }

            if (in_array('ide-helper:models', $argv)) {
                $this->registerIdeHelperModelLocations();
            }
        });
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/osdd.php', 'osdd');

        $this->app->singleton(SeederRegistry::class, fn () => new SeederRegistry);

        $this->app->singleton(MigrateMakeCommand::class, fn (Application $app): MigrateMakeCommand => new MigrateMakeCommand(
            $app->make('migration.creator'),
            $app->make('composer'),
        ));
    }

    private function registerCommands(): void
    {
        $this->commands([
            LayerCommand::class,
            StartCommand::class,
            PhpunitCommand::class,
            SeedCommand::class,
            CastMakeCommand::class,
            ChannelMakeCommand::class,
            ClassMakeCommand::class,
            ConfigMakeCommand::class,
            ConsoleMakeCommand::class,
            ControllerMakeCommand::class,
            EnumMakeCommand::class,
            EventMakeCommand::class,
            ExceptionMakeCommand::class,
            FactoryMakeCommand::class,
            InterfaceMakeCommand::class,
            JobMakeCommand::class,
            ListenerMakeCommand::class,
            MailMakeCommand::class,
            MiddlewareMakeCommand::class,
            MigrateMakeCommand::class,
            ModelMakeCommand::class,
            NotificationMakeCommand::class,
            ObserverMakeCommand::class,
            PolicyMakeCommand::class,
            RequestMakeCommand::class,
            ResourceMakeCommand::class,
            RuleMakeCommand::class,
            ScopeMakeCommand::class,
            SeederMakeCommand::class,
            ServiceProviderMakeCommand::class,
            TestMakeCommand::class,
            TraitMakeCommand::class,
            ViewMakeCommand::class,
        ]);
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/osdd.php' => config_path('osdd.php'),
        ]);
    }

    private function registerTinkerAliases(): void
    {
        [$namespaces, $map] = $this->buildLayerClassMap();

        $config = $this->app->make('config');

        /** @var array<int, string> $aliases */
        $aliases = $config->get('tinker.alias', []);

        $config->set('tinker.alias', array_merge($aliases, $namespaces));

        spl_autoload_register(function (string $class) use ($map): void {
            if (str_contains($class, '\\') || ! isset($map[$class])) {
                return;
            }

            require_once $map[$class]['path'];
            class_alias($map[$class]['fqcn'], $class);
        });
    }

    private function registerIdeHelperModelLocations(): void
    {
        $config = $this->app->make('config');

        if (! $config->has('ide-helper.model_locations')) {
            return;
        }

        /** @var list<string> $locations */
        $locations = $config->get('ide-helper.model_locations', []);

        foreach (LayersCollection::fromConfig() as $layer) {
            $srcPath = rtrim($layer->manifest->srcPath($layer->path), '/\\');

            if (is_dir($srcPath)) {
                $locations[] = $srcPath;
            }
        }

        $config->set('ide-helper.model_locations', array_values(array_unique($locations)));
    }

    /**
     * @return array{list<string>, array<string, array{fqcn: string, path: string}>}
     */
    private function buildLayerClassMap(): array
    {
        $namespaces = [];
        $map = [];

        foreach (LayersCollection::fromConfig() as $layer) {
            $namespace = $layer->manifest->rootNamespace();
            $srcPath = rtrim($layer->manifest->srcPath($layer->path), '/\\');

            $namespaces[] = $namespace;

            if (! is_dir($srcPath)) {
                continue;
            }

            foreach ((new Finder)->files()->name('*.php')->in($srcPath) as $file) {
                $relative = substr($file->getRealPath(), strlen($srcPath) + 1);
                $fqcn = $namespace.str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
                $shortName = basename($relative, '.php');

                $map[$shortName] ??= ['fqcn' => $fqcn, 'path' => $file->getRealPath()];
            }
        }

        return [$namespaces, $map];
    }
}
