<?php

namespace Lomkit\Osdd\Generation;

use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\Layers;

final readonly class GenerationTarget
{
    private const string MODELS_NAMESPACE = 'Models\\';

    public function __construct(
        public Layer $layer,
        public LayerResourceNamespace $resourceNamespace,
        private Layers $layers,
    ) {}

    public function source(): ClassLocation
    {
        return new ClassLocation($this->layer->rootNamespace, $this->layer->sourceDirectory);
    }

    public function folder(LayerFolder $folder): ClassLocation
    {
        return new ClassLocation($this->layer->rootNamespace.$folder->namespace().'\\', $this->layer->directory->path($folder));
    }

    public function viewPath(string $path = ''): string
    {
        $views = $this->layer->directory->path(LayerFolder::Views);

        return $path === '' ? $views : $views.'/'.$path;
    }

    /**
     * @return class-string
     */
    public function modelClass(string $model): string
    {
        $model = str_replace('/', '\\', ltrim($model, '\\/'));

        /** @var class-string $qualified */
        $qualified = class_exists($model) || $this->layers->owning($model) !== null
            ? $model
            : $this->layer->rootNamespace.self::MODELS_NAMESPACE.$model;

        return $qualified;
    }
}
