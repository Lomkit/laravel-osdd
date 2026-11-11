<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:test')]
class TestMakeCommand extends \Illuminate\Foundation\Console\TestMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:test';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        $suffix = $this->option('unit') ? 'Tests\\Unit' : 'Tests\\Feature';

        return rtrim($rootNamespace, '\\').'\\'.$suffix;
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->rootNamespace, '\\');

        $suffix = $this->option('unit') ? 'Tests\\Unit\\' : 'Tests\\Feature\\';
        $relative = Str::replaceFirst($layerNamespace.'\\'.$suffix, '', $name);

        $directory = $this->option('unit') ? 'Unit/' : 'Feature/';

        return $layer->directory->path(LayerFolder::Tests).'/'.$directory.str_replace('\\', '/', $relative).'.php';
    }
}
