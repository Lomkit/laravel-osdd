<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:command')]
class ConsoleMakeCommand extends \Illuminate\Foundation\Console\ConsoleMakeCommand
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    protected $name = 'osdd:command';

    #[\Override]
    public function handle(): ?bool
    {
        $created = parent::handle();

        $commandClass = $this->qualifyClass($this->getNameInput());

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->commands([\\{$commandClass}::class]);",
            $commandClass,
        );

        return $created;
    }

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Console\\Commands';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
