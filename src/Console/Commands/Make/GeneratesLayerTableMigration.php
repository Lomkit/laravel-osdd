<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Console\MigrationGeneratorCommand;
use Lomkit\Osdd\Console\Migrations\ExistingTableMigrations;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;

/**
 * @phpstan-require-extends MigrationGeneratorCommand
 */
trait GeneratesLayerTableMigration
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    private const string MIGRATIONS_FOLDER = 'database/migrations';

    #[\Override]
    public function handle(): int
    {
        $exitCode = parent::handle();

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (LayerProvider $provider): string => "\$this->loadMigrationsFrom({$provider->pathExpression(self::MIGRATIONS_FOLDER)});",
            self::MIGRATIONS_FOLDER,
        );

        return $exitCode;
    }

    /**
     * @param  string  $table
     */
    #[\Override]
    protected function createBaseMigration($table): string
    {
        return $this->laravel->make('migration.creator')->create(
            'create_'.$table.'_table',
            $this->resolveLayer()->path.'/'.self::MIGRATIONS_FOLDER,
        );
    }

    /**
     * @param  string  $table
     */
    #[\Override]
    protected function migrationExists($table): bool
    {
        if (parent::migrationExists($table)) {
            return true;
        }

        return $this->laravel->make(ExistingTableMigrations::class)->exists($table, $this->knownMigrationDirectories());
    }

    /**
     * @return list<string>
     */
    private function knownMigrationDirectories(): array
    {
        $layerDirectories = LayersCollection::fromConfig()->toBase()
            ->map(fn (Layer $layer): string => $layer->path.'/'.self::MIGRATIONS_FOLDER)
            ->all();

        /** @var list<string> $migratorPaths */
        $migratorPaths = $this->laravel->make('migrator')->paths();

        return [
            $this->resolveLayer()->path.'/'.self::MIGRATIONS_FOLDER,
            ...array_values($layerDirectories),
            ...$migratorPaths,
        ];
    }
}
