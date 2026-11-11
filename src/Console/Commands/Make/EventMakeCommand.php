<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:event')]
class EventMakeCommand extends \Illuminate\Foundation\Console\EventMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:event';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Events';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
