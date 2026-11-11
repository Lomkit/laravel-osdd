<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Layers\AmbiguousLayerNameException;
use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\LayerNameList;
use Lomkit\Osdd\Layers\LayerNotFoundException;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Seeders\LayersSeeder;
use Lomkit\Osdd\Seeders\SeederSelection;
use Lomkit\Osdd\Seeders\SeedersOfLayers;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:seed')]
class SeedCommand extends Command
{
    use ReportsOsddConsoleExceptions;

    protected $description = 'Run the seeders registered by the OSDD layers';

    protected $signature = 'osdd:seed
        {--layer=* : Only run the seeders of these layers (full or package name, repeatable or comma separated)}
        {--fresh : Run migrate:fresh before seeding}
        {--refresh : Run migrate:refresh before seeding}
        {--database= : The database connection to seed}
        {--force : Force the operation to run when in production}';

    public function handle(): int
    {
        if ($this->option('fresh') && $this->option('refresh')) {
            $this->components->error('Pass either --fresh or --refresh, not both.');

            return self::FAILURE;
        }

        $selection = $this->layerSelection();

        $migration = match (true) {
            (bool) $this->option('fresh') => 'migrate:fresh',
            (bool) $this->option('refresh') => 'migrate:refresh',
            default => null,
        };

        if ($migration !== null && ($exitCode = $this->call($migration, $this->forwardedOptions())) !== self::SUCCESS) {
            return $exitCode;
        }

        if ($selection === null) {
            return $this->seed();
        }

        $this->laravel->instance(SeederSelection::class, $selection);

        try {
            return $this->seed();
        } finally {
            $this->laravel->forgetInstance(SeederSelection::class);
        }
    }

    private function seed(): int
    {
        return $this->call('db:seed', ['--class' => LayersSeeder::class, ...$this->forwardedOptions()]);
    }

    /**
     * @throws LayerNotFoundException
     * @throws AmbiguousLayerNameException
     * @throws InvalidLayerManifestException
     */
    private function layerSelection(): ?SeedersOfLayers
    {
        $requested = LayerNameList::parse((array) $this->option('layer'));

        if ($requested === []) {
            return null;
        }

        $layers = $this->laravel->make(Layers::class)->failOnInvalidManifest();

        return SeedersOfLayers::of($layers->findAll($requested), $layers);
    }

    /**
     * @return array<string, mixed>
     */
    private function forwardedOptions(): array
    {
        return array_filter([
            '--database' => $this->option('database'),
            '--force' => $this->option('force'),
        ]);
    }
}
