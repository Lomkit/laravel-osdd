<?php

namespace Lomkit\Osdd\Layers;

final class RequiredLayers
{
    /**
     * @param  array<array-key, mixed>  $values
     * @return list<string>
     *
     * @throws LayerNotFoundException
     * @throws AmbiguousLayerNameException
     * @throws LayerRequiresItselfException
     */
    public static function resolve(array $values, LayerName $layer, Layers $layers): array
    {
        $names = LayerNameList::parse($values);

        if ($names === []) {
            return [];
        }

        $required = $layers->findAll($names)->names();

        if (in_array($layer->toString(), $required, true)) {
            throw new LayerRequiresItselfException($layer->toString());
        }

        return $required;
    }
}
