<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:channel')]
class ChannelMakeCommand extends \Illuminate\Foundation\Console\ChannelMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:channel';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Broadcasting';
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\Broadcasting\\', '', $name);

        return $layer->path.'/src/Broadcasting/'.str_replace('\\', '/', $relative).'.php';
    }
}
