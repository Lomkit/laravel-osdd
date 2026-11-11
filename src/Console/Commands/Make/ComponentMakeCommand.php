<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:component')]
final class ComponentMakeCommand extends \Illuminate\Foundation\Console\ComponentMakeCommand
{
    use GeneratesClassesInLayer;
    use InjectsResourceHelpers;

    #[\Override]
    public function handle(): void
    {
        $this->warnWhenTheResourceNamespaceIsShared();

        parent::handle();

        if (! $this->option('inline')) {
            $this->injectWithViews();
        }

        if (! $this->option('view')) {
            $this->injectWithComponents($this->getDefaultNamespace(trim($this->rootNamespace(), '\\')));
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

        return str_replace("view('{$view}')", "view('{$this->layerViewNamespace()}::{$view}')", $class);
    }

    #[\Override]
    protected function viewPath($path = ''): string
    {
        return $this->target()->viewPath($path);
    }
}
