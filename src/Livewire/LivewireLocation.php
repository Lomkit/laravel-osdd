<?php

namespace Lomkit\Osdd\Livewire;

use Livewire\LivewireManager;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerFolder;

final readonly class LivewireLocation
{
    private const string CLASS_FOLDER = 'Livewire';

    private const string VIEW_FOLDER = 'livewire';

    private function __construct(
        public string $namespace,
        public string $classNamespace,
        public string $classPath,
        public string $viewPath,
    ) {}

    public static function of(Layer $layer, string $namespace): self
    {
        return new self(
            $namespace,
            $layer->rootNamespace.self::CLASS_FOLDER,
            $layer->sourceDirectory.'/'.self::CLASS_FOLDER,
            $layer->directory->path(LayerFolder::Views).'/'.self::VIEW_FOLDER,
        );
    }

    public function registerIn(LivewireManager $livewire): void
    {
        $livewire->addNamespace(
            $this->namespace,
            viewPath: $this->viewPath,
            classNamespace: $this->classNamespace,
            classPath: $this->classPath,
            classViewPath: $this->viewPath,
        );
    }

    public function componentName(string $name): string
    {
        return $this->namespace.'::'.$name;
    }

    public function classViewPrefix(string $viewNamespace): string
    {
        return $viewNamespace.'::'.self::VIEW_FOLDER;
    }
}
