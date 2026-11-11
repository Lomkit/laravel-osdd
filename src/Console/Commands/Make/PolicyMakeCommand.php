<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:policy')]
class PolicyMakeCommand extends \Illuminate\Foundation\Console\PolicyMakeCommand
{
    use ChoosesOsddLayer;
    use QualifiesModelsAcrossLayers;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:policy';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Policies';
    }

    #[\Override]
    protected function replaceUserNamespace($stub): string
    {
        $model = $this->userProviderModel();

        if (! $model) {
            return $stub;
        }

        return str_replace(
            ['{{ namespacedUserModel }}', '{{namespacedUserModel}}', $this->rootNamespace().'User'],
            $model,
            $stub
        );
    }

    #[\Override]
    protected function getStub(): string
    {
        if ($this->option('model')) {
            return parent::getStub();
        }

        return __DIR__.'/../../../Stubs/make/policy.stub';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
