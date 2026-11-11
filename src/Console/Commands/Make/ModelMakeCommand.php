<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'osdd:model')]
class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    use ChoosesOsddLayer {
        configureUsingFluentDefinition as configureLayerDefinition;
    }
    use CreatesModelCompanions;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:model';

    #[\Override]
    protected function getStub(): string
    {
        if ($this->option('factory')) {
            return __DIR__.'/../../stubs/make/model.factory.stub';
        }

        return parent::getStub();
    }

    #[\Override]
    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        if ($this->option('factory')) {
            $rootNamespace = rtrim($this->resolveLayer()->manifest->rootNamespace(), '\\');
            $modelClass = class_basename($name);

            $stub = str_replace(
                ['{{ factoryNamespace }}', '{{ factoryClass }}'],
                [$rootNamespace.'\\Database\\Factories', $modelClass.'Factory'],
                $stub,
            );
        }

        return $stub;
    }

    #[\Override]
    public function handle(): ?bool
    {
        // Bypass ModelMakeCommand::handle()'s interactive "additional components" prompt
        // by calling GeneratorCommand::handle() directly via grandparent scope binding.
        $grandparentHandle = \Closure::bind(
            fn () => parent::handle(),
            $this,
            \Illuminate\Foundation\Console\ModelMakeCommand::class
        );

        $result = $grandparentHandle();

        if ($result === false && ! $this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        } elseif ($this->option('requests')) {
            $this->createFormRequests();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }

        return null;
    }

    #[\Override]
    protected function configureUsingFluentDefinition(): void
    {
        $this->configureLayerDefinition();

        $this->getDefinition()->addOption(new InputOption('tenant', null, InputOption::VALUE_NONE, 'Create the migration and the seeder for the tenant databases'));
    }

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Models';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
