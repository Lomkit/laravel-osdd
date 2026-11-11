<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;

trait CreatesModelCompanions
{
    #[\Override]
    protected function createFactory(): void
    {
        $factory = Str::studly($this->argument('name'));

        $this->call('osdd:factory', [
            'name' => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    #[\Override]
    protected function createController(): void
    {
        $controller = Str::studly($this->argument('name'));

        $this->call('osdd:controller', array_filter([
            'name' => "{$controller}Controller",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--resource' => true,
            '--requests' => $this->option('requests'),
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]));
    }

    #[\Override]
    protected function createFormRequests(): void
    {
        $request = Str::studly($this->argument('name'));

        $this->call('osdd:request', [
            'name' => "Store{$request}Request",
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);

        $this->call('osdd:request', [
            'name' => "Update{$request}Request",
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    #[\Override]
    protected function createPolicy(): void
    {
        $policy = Str::studly($this->argument('name'));

        $this->call('osdd:policy', [
            'name' => "{$policy}Policy",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    #[\Override]
    protected function createSeeder(): void
    {
        $seeder = Str::studly($this->argument('name'));

        $this->call('osdd:seeder', [
            'name' => "{$seeder}Seeder",
            '--layer' => $this->resolveLayer()->manifest->name(),
            '--tenant' => $this->option('tenant'),
        ]);
    }

    #[\Override]
    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('osdd:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
            '--layer' => $this->resolveLayer()->manifest->name(),
            '--tenant' => $this->option('tenant'),
        ]);
    }
}
