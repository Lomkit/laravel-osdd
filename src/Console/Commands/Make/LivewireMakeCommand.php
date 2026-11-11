<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Livewire\Features\SupportConsoleCommands\Commands\MakeCommand;
use Livewire\LivewireManager;
use Lomkit\Osdd\Console\Providers\Statements\WithLivewire;
use Lomkit\Osdd\Generation\GeneratesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Livewire\LivewireLocation;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:livewire')]
final class LivewireMakeCommand extends MakeCommand
{
    use GeneratesInLayer;
    use InjectsResourceHelpers;

    private const string NAMESPACE_SEPARATOR = '::';

    #[\Override]
    public function handle(): int
    {
        $this->warnWhenTheResourceNamespaceIsShared();
        $this->location()->registerIn($this->laravel->make(LivewireManager::class));

        $status = parent::handle();

        if ($status === self::SUCCESS) {
            $this->wireInTargetLayer(new WithLivewire($this->target()->resourceNamespace));
        }

        return $status;
    }

    #[\Override]
    protected function normalizeComponentName(string $name): string
    {
        return $this->location()->componentName(parent::normalizeComponentName(Str::afterLast($name, self::NAMESPACE_SEPARATOR)));
    }

    #[\Override]
    protected function createClassBasedComponent(string $name): int
    {
        $status = parent::createClassBasedComponent($name);

        if ($status === self::SUCCESS) {
            $this->injectWithViews();
        }

        return $status;
    }

    #[\Override]
    protected function extractViewNamespace(string $viewPath): string
    {
        return $viewPath === $this->location()->viewPath
            ? $this->location()->classViewPrefix($this->layerViewNamespace())
            : parent::extractViewNamespace($viewPath);
    }

    #[\Override]
    protected function getClassBasedComponentTestPath(string $name): string
    {
        $applicationTest = parent::getClassBasedComponentTestPath(Str::afterLast($name, self::NAMESPACE_SEPARATOR));

        return $this->target()->layer->directory->path(LayerFolder::Tests).Str::after($applicationTest, $this->laravel->basePath(LayerFolder::Tests->value));
    }

    private function location(): LivewireLocation
    {
        return LivewireLocation::of($this->target()->layer, $this->target()->resourceNamespace->name());
    }
}
