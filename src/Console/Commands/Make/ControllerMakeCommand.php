<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\ControllerModelReplacements;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:controller')]
final class ControllerMakeCommand extends \Illuminate\Routing\Console\ControllerMakeCommand
{
    use GeneratesClassesInLayer;

    private const string FRAMEWORK_REQUEST_NAMESPACE = 'App\\Http\\Requests\\';

    private const string REQUEST_NAMESPACE = 'Http\\Requests\\';

    /**
     * @param  array<string, string>  $replace
     * @return array<string, string>
     */
    #[\Override]
    protected function buildModelReplacements(array $replace): array
    {
        $modelClass = $this->parseModel((string) $this->option('model'));

        return [
            ...$this->buildFormRequestReplacements($replace, $modelClass),
            ...ControllerModelReplacements::of($modelClass),
        ];
    }

    /**
     * @param  array<string, string>  $replace
     * @param  string  $modelClass
     * @return array<string, string>
     */
    #[\Override]
    protected function buildFormRequestReplacements(array $replace, $modelClass): array
    {
        /** @var array<string, string> $replacements */
        $replacements = parent::buildFormRequestReplacements($replace, $modelClass);
        $requestNamespace = $this->rootNamespace().self::REQUEST_NAMESPACE;

        return array_map(
            fn (string $value): string => str_replace(self::FRAMEWORK_REQUEST_NAMESPACE, $requestNamespace, $value),
            $replacements,
        );
    }

    /**
     * @param  string  $modelClass
     * @param  string  $storeRequestClass
     * @param  string  $updateRequestClass
     * @return array{string, string}
     */
    #[\Override]
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass): array
    {
        $storeRequestClass = 'Store'.class_basename($modelClass).'Request';
        $updateRequestClass = 'Update'.class_basename($modelClass).'Request';

        $this->generateInTargetLayer(RequestMakeCommand::class, ['name' => $storeRequestClass]);
        $this->generateInTargetLayer(RequestMakeCommand::class, ['name' => $updateRequestClass]);

        return [$storeRequestClass, $updateRequestClass];
    }
}
