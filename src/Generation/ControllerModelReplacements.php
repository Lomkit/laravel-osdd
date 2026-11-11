<?php

namespace Lomkit\Osdd\Generation;

final class ControllerModelReplacements
{
    /**
     * @return array<string, string>
     */
    public static function of(string $modelClass): array
    {
        $model = class_basename($modelClass);
        $variable = lcfirst($model);

        return [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => $model,
            '{{ model }}' => $model,
            '{{model}}' => $model,
            'DummyModelVariable' => $variable,
            '{{ modelVariable }}' => $variable,
            '{{modelVariable}}' => $variable,
        ];
    }
}
