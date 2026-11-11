<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Stubs\Stub;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:model')]
final class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    protected function getStub(): string
    {
        if ($this->option('factory')) {
            return Stub::ModelWithFactory->path();
        }

        return parent::getStub();
    }

    #[\Override]
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        if (! $this->option('factory')) {
            return $stub;
        }

        return str_replace(
            ['{{ factoryNamespace }}', '{{ factoryClass }}'],
            [rtrim($this->target()->folder(LayerFolder::Factories)->namespace, '\\'), class_basename($name).'Factory'],
            $stub,
        );
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Models';
    }

    #[\Override]
    protected function createFactory(): void
    {
        $this->generateInTargetLayer(FactoryMakeCommand::class, [
            'name' => Str::studly($this->argument('name')).'Factory',
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    #[\Override]
    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->generateInTargetLayer(MigrateMakeCommand::class, [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    #[\Override]
    protected function createSeeder(): void
    {
        $this->generateInTargetLayer(SeederMakeCommand::class, [
            'name' => Str::studly($this->argument('name')).'Seeder',
        ]);
    }

    #[\Override]
    protected function createController(): void
    {
        $this->generateInTargetLayer(ControllerMakeCommand::class, array_filter([
            'name' => Str::studly(class_basename($this->argument('name'))).'Controller',
            '--model' => $this->option('resource') || $this->option('api') ? $this->qualifyClass($this->getNameInput()) : null,
            '--api' => $this->option('api'),
            '--requests' => $this->option('requests') || $this->option('all'),
            '--test' => $this->option('test'),
            '--pest' => $this->option('pest'),
        ]));
    }

    #[\Override]
    protected function createFormRequests(): void
    {
        $request = Str::studly($this->argument('name'));

        $this->generateInTargetLayer(RequestMakeCommand::class, ['name' => "Store{$request}Request"]);
        $this->generateInTargetLayer(RequestMakeCommand::class, ['name' => "Update{$request}Request"]);
    }

    #[\Override]
    protected function createPolicy(): void
    {
        $this->generateInTargetLayer(PolicyMakeCommand::class, [
            'name' => Str::studly($this->argument('name')).'Policy',
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }
}
