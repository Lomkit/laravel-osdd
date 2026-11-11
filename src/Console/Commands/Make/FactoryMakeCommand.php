<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:factory')]
class FactoryMakeCommand extends \Illuminate\Database\Console\Factories\FactoryMakeCommand
{
    use ChoosesOsddLayer;
    use QualifiesModelsAcrossLayers;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:factory';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Database\\Factories';
    }

    #[\Override]
    protected function buildClass($name): string
    {
        $wrongNamespace = $this->getNamespace(
            Str::replaceFirst($this->rootNamespace(), 'Database\\Factories\\', $name)
        );

        return str_replace($wrongNamespace, $this->getNamespace($name), parent::buildClass($name));
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->rootNamespace, '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\Database\\Factories\\', '', $name);

        return $layer->directory->path(LayerFolder::Factories).'/'.str_replace('\\', '/', $relative).'.php';
    }
}
