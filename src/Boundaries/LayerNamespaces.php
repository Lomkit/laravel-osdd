<?php

namespace Lomkit\Osdd\Boundaries;

use Lomkit\Osdd\Layers\Layers;

final class LayerNamespaces
{
    /**
     * @param  array<string, string>  $layerByPrefix
     */
    private function __construct(private readonly array $layerByPrefix) {}

    public static function of(Layers $layers): self
    {
        $layerByPrefix = [];

        foreach ($layers as $layer) {
            foreach ([...$layer->namespaces(), ...$layer->devNamespaces()] as $prefix) {
                $prefix = trim($prefix, '\\');

                if ($prefix !== '') {
                    $layerByPrefix[$prefix.'\\'] = $layer->name->toString();
                }
            }
        }

        uksort($layerByPrefix, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return new self($layerByPrefix);
    }

    public function layerOf(string $name): ?string
    {
        foreach ($this->layerByPrefix as $prefix => $layer) {
            if (str_starts_with($name, $prefix)) {
                return $layer;
            }
        }

        return null;
    }
}
