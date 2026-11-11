<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Console\MigrationGeneratorCommand;
use Lomkit\Osdd\Console\Migrations\ExistingTableMigrations;
use Lomkit\Osdd\Console\Providers\Statements\LoadMigrations;
use Lomkit\Osdd\Generation\GeneratesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\Layers;

/**
 * @phpstan-require-extends MigrationGeneratorCommand
 */
trait GeneratesLayerTableMigration
{
    use GeneratesInLayer;

    #[\Override]
    public function handle(): int
    {
        $exitCode = parent::handle();

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $this->wireInTargetLayer(new LoadMigrations);

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
            $this->target()->layer->directory->path(LayerFolder::Migrations),
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
        /** @var list<string> $migratorPaths */
        $migratorPaths = $this->laravel->make('migrator')->paths();

        return [
            $this->target()->layer->directory->path(LayerFolder::Migrations),
            ...$this->laravel->make(Layers::class)->paths(LayerFolder::Migrations),
            ...$migratorPaths,
        ];
    }
}
