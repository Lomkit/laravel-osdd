<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:cast')]
class CastMakeCommand extends \Illuminate\Foundation\Console\CastMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:cast';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Casts';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
