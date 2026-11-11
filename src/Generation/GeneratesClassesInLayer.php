<?php

namespace Lomkit\Osdd\Generation;

use Illuminate\Console\GeneratorCommand;

/**
 * @internal
 *
 * @phpstan-require-extends GeneratorCommand
 */
trait GeneratesClassesInLayer
{
    use GeneratesInLayer;

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->classLocation()->namespace;
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    protected function getPath($name): string
    {
        return $this->classLocation()->fileOf($name);
    }

    /**
     * @return class-string
     */
    #[\Override]
    protected function qualifyModel(string $model): string
    {
        return $this->target()->modelClass($model);
    }

    protected function classLocation(): ClassLocation
    {
        return $this->target()->source();
    }
}
