<?php

namespace Lomkit\Osdd\Layers;

final class LayerNameList
{
    /**
     * @param  array<array-key, mixed>  $values
     * @return list<string>
     */
    public static function parse(array $values): array
    {
        $names = array_map(trim(...), explode(',', implode(',', array_filter($values, is_string(...)))));

        return array_values(array_unique(array_filter($names, fn (string $name): bool => $name !== '')));
    }
}
