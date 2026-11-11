<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:policy')]
class PolicyMakeCommand extends \Illuminate\Foundation\Console\PolicyMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:policy';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Policies';
    }

    #[\Override]
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);
        $rootNamespace = $this->rootNamespace();

        /** @var class-string $qualified */
        $qualified = Str::startsWith($model, $rootNamespace) ? $model : $rootNamespace.'Models\\'.$model;

        return $qualified;
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

        return __DIR__.'/../../stubs/make/policy.stub';
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\Policies\\', '', $name);

        return $layer->path.'/src/Policies/'.str_replace('\\', '/', $relative).'.php';
    }
}
