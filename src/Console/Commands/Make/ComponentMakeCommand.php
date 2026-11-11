<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\BootedLayerResources;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:component')]
class ComponentMakeCommand extends \Illuminate\Foundation\Console\ComponentMakeCommand
{
    use ChoosesOsddLayer;
    use InjectsResourceHelpers;

    protected $name = 'osdd:component';

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        if (! $this->option('inline')) {
            $this->injectWithViews();
        }

        if (! $this->option('view')) {
            $this->injectWithComponents($this->componentNamespace());
        }
    }

    #[\Override]
    protected function buildClass($name): string
    {
        $class = parent::buildClass($name);

        if ($this->option('inline')) {
            return $class;
        }

        $view = $this->getView();

        return str_replace("view('{$view}')", "view('{$this->viewNamespace()}::{$view}')", $class);
    }

    #[\Override]
    protected function viewPath($path = ''): string
    {
        return $this->resolveLayer()->path.'/resources/views'.($path === '' ? '' : '/'.$path);
    }

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\View\\Components';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }

    private function componentNamespace(): string
    {
        return $this->getDefaultNamespace($this->rootNamespace());
    }

    private function viewNamespace(): string
    {
        return $this->laravel->make(BootedLayerResources::class)->viewNamespace($this->resolveLayer())
            ?? $this->resourceNamespace()->name();
    }
}
