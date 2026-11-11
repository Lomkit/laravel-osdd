<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:job-middleware')]
class JobMiddlewareMakeCommand extends \Illuminate\Foundation\Console\JobMiddlewareMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:job-middleware';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Jobs\\Middleware';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }
}
