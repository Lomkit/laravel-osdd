<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Generation\ClassLocation;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:factory')]
final class FactoryMakeCommand extends \Illuminate\Database\Console\Factories\FactoryMakeCommand
{
    use GeneratesClassesInLayer;

    private const string NAMESPACE_PLACEHOLDER = '{{ factoryNamespace }}';

    private const string CLASS_SUFFIX = 'Factory';

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($this->factories()->namespace, '\\');
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    protected function getPath($name): string
    {
        return $this->factories()->fileOf(Str::finish($name, self::CLASS_SUFFIX));
    }

    /**
     * @param  string  $stub
     * @param  string  $name
     */
    #[\Override]
    protected function replaceNamespace(&$stub, $name): static
    {
        $stub = str_replace(self::NAMESPACE_PLACEHOLDER, $this->getNamespace($name), $stub);

        return parent::replaceNamespace($stub, $name);
    }

    private function factories(): ClassLocation
    {
        return $this->target()->folder(LayerFolder::Factories);
    }
}
