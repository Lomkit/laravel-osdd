<?php

namespace Lomkit\Osdd\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\LayersCollection;

final class LayerGeneratorRunner
{
    /**
     * @param  list<LayerGenerator>  $generators
     */
    public function __construct(
        private readonly Command $command,
        private readonly StubWriter $stubs,
        private readonly Filesystem $files,
        private readonly LayerName $name,
        private readonly string $layerPath,
        private readonly array $generators,
        private readonly bool $force,
    ) {}

    public function run(): void
    {
        foreach ($this->executionOrder() as $generator) {
            $this->runGenerator($generator);
        }
    }

    /**
     * The provider comes first so the generators that register themselves in it find it;
     * the factory comes with the model when both are selected.
     *
     * @return list<LayerGenerator>
     */
    private function executionOrder(): array
    {
        $others = array_filter(
            $this->generators,
            fn (LayerGenerator $generator): bool => $generator !== LayerGenerator::ServiceProvider
                && ! ($generator === LayerGenerator::Factory && $this->selected(LayerGenerator::Model)),
        );

        return [
            ...($this->selected(LayerGenerator::ServiceProvider) ? [LayerGenerator::ServiceProvider] : []),
            ...array_values($others),
        ];
    }

    private function runGenerator(LayerGenerator $generator): void
    {
        $studly = $this->name->studlyPackage();
        $singular = Str::singular($studly);

        match ($generator) {
            LayerGenerator::Migration => $this->generateMigration(Str::snake(Str::pluralStudly($studly))),
            LayerGenerator::Model => $this->callGenerator('osdd:model', ['name' => $singular, '--factory' => $this->selected(LayerGenerator::Factory)]),
            LayerGenerator::Factory => $this->callGenerator('osdd:factory', ['name' => "{$singular}Factory"]),
            LayerGenerator::Seeder => $this->callGenerator('osdd:seeder', ['name' => "{$studly}Seeder"]),
            LayerGenerator::ServiceProvider => $this->generateServiceProvider($studly.'ServiceProvider'),
            LayerGenerator::Test => $this->callGenerator('osdd:test', ['name' => "{$studly}Test"]),
            LayerGenerator::Controller => $this->callGenerator('osdd:controller', ['name' => "{$studly}Controller"]),
            LayerGenerator::Policy => $this->callGenerator('osdd:policy', ['name' => "{$studly}Policy", '--model' => $this->selected(LayerGenerator::Model) ? $singular : null]),
            LayerGenerator::Routes => $this->generateRoutes(),
        };
    }

    private function selected(LayerGenerator $generator): bool
    {
        return in_array($generator, $this->generators, true);
    }

    /**
     * @param  array<string, string|bool|null>  $arguments
     */
    private function callGenerator(string $command, array $arguments): void
    {
        $this->command->call($command, array_filter([
            ...$arguments,
            '--layer' => $this->name->toString(),
            '--force' => $this->force && $this->acceptsForce($command),
        ]));
    }

    private function acceptsForce(string $command): bool
    {
        return $this->command->getApplication()?->find($command)->getDefinition()->hasOption('force') ?? false;
    }

    private function generateMigration(string $table): void
    {
        if ($this->files->glob($this->layerPath."/database/migrations/*_create_{$table}_table.php") !== []) {
            $this->command->warn("A create_{$table}_table migration already exists, skipping the migration generator.");

            return;
        }

        $this->command->call('osdd:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
            '--layer' => $this->name->toString(),
        ]);
    }

    private function generateServiceProvider(string $class): void
    {
        $this->stubs->write('layer/service-provider', $this->layerPath.'/src/Providers/'.$class.'.php', [
            '{{ namespace }}' => $this->name->namespace(),
            '{{ class }}' => $class,
            '{{ resourceNamespace }}' => LayerResourceNamespace::of($this->name->toString(), LayersCollection::fromConfig())->argument(),
        ]);

        $composer = ComposerJsonFile::open($this->files, $this->layerPath.'/composer.json');
        $composer->addLaravelProvider($this->name->namespace().'\\Providers\\'.$class);
        $composer->save();
    }

    private function generateRoutes(): void
    {
        foreach (['web', 'api', 'console', 'channels'] as $file) {
            $this->stubs->write('layer/routes/'.$file, $this->layerPath.'/routes/'.$file.'.php');
        }
    }
}
