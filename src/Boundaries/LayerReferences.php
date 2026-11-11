<?php

namespace Lomkit\Osdd\Boundaries;

use Lomkit\Osdd\Boundaries\Php\ReferencedNames;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Support\Path;

final class LayerReferences
{
    public function __construct(
        private readonly LayerNamespaces $namespaces,
        private readonly string $basePath,
    ) {}

    /**
     * The first reference of each file to each class of another layer.
     *
     * @return list<LayerReference>
     */
    public function of(Layer $layer): array
    {
        $references = [];

        foreach (LayerSourceFiles::of($layer) as $path => $fromTests) {
            $file = Path::relative($this->basePath, $path);
            $seen = [];

            foreach (ReferencedNames::inFile($path) as $reference) {
                $target = $this->namespaces->layerOf($reference->name);

                if ($target === null || $target === $layer->manifest->name() || isset($seen[$reference->name])) {
                    continue;
                }

                $seen[$reference->name] = true;
                $references[] = new LayerReference($target, $reference->name, $file, $reference->line, $fromTests);
            }
        }

        return $references;
    }
}
