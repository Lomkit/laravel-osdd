<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\SeederRegistry;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:seed')]
class SeedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the seeder for all discovered OSDD layers';

    protected $signature = 'osdd:seed
        {--fresh : Run migrate:fresh before seeding}
        {--refresh : Run migrate:refresh before seeding}
        {--database= : The database connection to seed}
        {--force : Force the operation to run when in production}';

    public function handle(SeederRegistry $registry): int
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

        $seeders = $registry->seeders();

        if (empty($seeders)) {
            $this->warn('No OSDD seeders registered. Make sure your layer ServiceProviders call loadSeeders().');

            return self::SUCCESS;
        }

        foreach ($seeders as $seederClass) {
            if (! class_exists($seederClass)) {
                $this->warn("Seeder class <comment>{$seederClass}</comment> not found, skipping.");

                continue;
            }

            $this->info("Seeding <comment>{$seederClass}</comment>...");

            if (($exitCode = $this->call('db:seed', ['--class' => $seederClass, ...$this->forwardedOptions()])) !== self::SUCCESS) {
                $this->components->error("Seeder {$seederClass} failed; the next seeders were not run.");

                return $exitCode;
            }
        }

        return self::SUCCESS;
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
