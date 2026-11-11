<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:middleware')]
class MiddlewareMakeCommand extends \Illuminate\Routing\Console\MiddlewareMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:middleware';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Http\\Middleware';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
