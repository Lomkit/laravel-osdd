<?php

namespace Lomkit\Osdd\Console\Providers;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\Source\DeclaredParent;
use Lomkit\Osdd\LayerServiceProvider;

final readonly class ProviderClass
{
    public function __construct(
        private string $class,
        private string $file,
    ) {}

    /**
     * A class the autoloader cannot load yet, such as the provider of a layer not installed, counts only when its file
     * names LayerServiceProvider as its direct parent.
     */
    public function extendsLayerServiceProvider(): bool
    {
        if (class_exists($this->class)) {
            return is_subclass_of($this->class, LayerServiceProvider::class);
        }

        $parent = DeclaredParent::of((string) file_get_contents($this->file), Str::afterLast($this->class, '\\'));

        return $parent !== null && strcasecmp($parent, LayerServiceProvider::class) === 0;
    }
}
