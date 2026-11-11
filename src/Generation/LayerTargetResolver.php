<?php

namespace Lomkit\Osdd\Generation;

use Lomkit\Osdd\Console\Exceptions\MissingLayerOptionException;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Layers\Layers;

use function Laravel\Prompts\search;

final class LayerTargetResolver
{
    public function __construct(private readonly Layers $layers) {}

    public function resolve(?string $requestedLayer, bool $canPrompt): GenerationTarget
    {
        $layers = $this->layers->failOnInvalidManifest();
        $layer = $layers->find($requestedLayer ?? $this->chosenLayer($canPrompt));

        return new GenerationTarget($layer, LayerResourceNamespace::of($layer->name, $layers), $layers);
    }

    private function chosenLayer(bool $canPrompt): string
    {
        if (! $canPrompt) {
            throw new MissingLayerOptionException($this->layers->names());
        }

        return (string) search(
            label: 'Which layer should this be generated in?',
            options: fn (string $value): array => $this->layersMatching($value),
        );
    }

    /**
     * @return array<string, string>
     */
    private function layersMatching(string $search): array
    {
        $names = array_filter($this->layers->names(), fn (string $name): bool => str_contains($name, $search));

        return array_combine($names, $names);
    }
}
