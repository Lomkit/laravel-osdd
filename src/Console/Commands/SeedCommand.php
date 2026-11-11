<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Seeders\LayersSeeder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:seed')]
class SeedCommand extends Command
{
    protected $description = 'Run the seeders registered by the OSDD layers';

    protected $signature = 'osdd:seed
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

        $migration = match (true) {
            (bool) $this->option('fresh') => 'migrate:fresh',
            (bool) $this->option('refresh') => 'migrate:refresh',
            default => null,
        };

        if ($migration !== null && ($exitCode = $this->call($migration, $this->forwardedOptions())) !== self::SUCCESS) {
            return $exitCode;
        }

        return $this->call('db:seed', ['--class' => LayersSeeder::class, ...$this->forwardedOptions()]);
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
