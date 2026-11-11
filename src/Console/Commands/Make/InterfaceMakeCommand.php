<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:interface')]
class InterfaceMakeCommand extends \Illuminate\Foundation\Console\InterfaceMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:interface';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Contracts';
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\Contracts\\', '', $name);

        return $layer->path.'/src/Contracts/'.str_replace('\\', '/', $relative).'.php';
    }
}
