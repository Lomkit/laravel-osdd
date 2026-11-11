<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:exception')]
class ExceptionMakeCommand extends \Illuminate\Foundation\Console\ExceptionMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:exception';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Exceptions';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
