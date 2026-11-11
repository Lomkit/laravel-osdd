<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:command')]
class ConsoleMakeCommand extends \Illuminate\Foundation\Console\ConsoleMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:command';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
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
