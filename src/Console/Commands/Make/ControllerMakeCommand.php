<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:controller')]
class ControllerMakeCommand extends \Illuminate\Routing\Console\ControllerMakeCommand
{
    use ChoosesOsddLayer;
    use QualifiesModelsAcrossLayers;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:controller';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Http\\Controllers';
    }

    #[\Override]
    protected function getPath($name): string
    {
        return $this->layerSourcePath($name);
    }

    /**
     * @param  array<string, string>  $replace
     * @return array<string, string>
     */
    #[\Override]
    protected function buildModelReplacements(array $replace): array
    {
        $modelClass = $this->parseModel((string) $this->option('model'));

        $replace = $this->buildFormRequestReplacements($replace, $modelClass);

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            '{{ namespacedModel }}' => $modelClass,
            '{{namespacedModel}}' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            '{{ model }}' => class_basename($modelClass),
            '{{model}}' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
            '{{ modelVariable }}' => lcfirst(class_basename($modelClass)),
            '{{modelVariable}}' => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * @param  array<string, string>  $replace
     * @return array<string, string>
     */
    #[\Override]
    protected function buildFormRequestReplacements(array $replace, $modelClass): array
    {
        [$namespace, $storeRequestClass, $updateRequestClass] = [
            'Illuminate\\Http', 'Request', 'Request',
        ];

        if ($this->option('requests')) {
            $layerNamespace = rtrim($this->resolveLayer()->manifest->rootNamespace(), '\\');
            $namespace = $layerNamespace.'\\Http\\Requests';

            [$storeRequestClass, $updateRequestClass] = $this->generateFormRequests(
                $modelClass, $storeRequestClass, $updateRequestClass
            );
        }

        $namespacedRequests = $namespace.'\\'.$storeRequestClass.';';

        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL.'use '.$namespace.'\\'.$updateRequestClass.';';
        }

        return array_merge($replace, [
            '{{ storeRequest }}' => $storeRequestClass,
            '{{storeRequest}}' => $storeRequestClass,
            '{{ updateRequest }}' => $updateRequestClass,
            '{{updateRequest}}' => $updateRequestClass,
            '{{ namespacedStoreRequest }}' => $namespace.'\\'.$storeRequestClass,
            '{{namespacedStoreRequest}}' => $namespace.'\\'.$storeRequestClass,
            '{{ namespacedUpdateRequest }}' => $namespace.'\\'.$updateRequestClass,
            '{{namespacedUpdateRequest}}' => $namespace.'\\'.$updateRequestClass,
            '{{ namespacedRequests }}' => $namespacedRequests,
            '{{namespacedRequests}}' => $namespacedRequests,
        ]);
    }

    /**
     * @return array{string, string}
     */
    #[\Override]
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass): array
    {
        $storeRequestClass = 'Store'.class_basename($modelClass).'Request';
        $updateRequestClass = 'Update'.class_basename($modelClass).'Request';

        $this->call('osdd:request', [
            'name' => $storeRequestClass,
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);

        $this->call('osdd:request', [
            'name' => $updateRequestClass,
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);

        return [$storeRequestClass, $updateRequestClass];
    }
}
