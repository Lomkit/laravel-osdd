<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:class')]
class ClassMakeCommand extends \Illuminate\Foundation\Console\ClassMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:class';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\', '', $name);

        return $layer->path.'/src/'.str_replace('\\', '/', $relative).'.php';
    }
}
