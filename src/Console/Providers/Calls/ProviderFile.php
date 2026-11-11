<?php

namespace Lomkit\Osdd\Console\Providers\Calls;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\Source\ProviderImports;

final readonly class ProviderFile
{
    public function __construct(
        private LayerProvider $provider,
        private ProviderImports $imports,
    ) {}

    public static function withoutImports(LayerProvider $provider): self
    {
        return new self($provider, ProviderImports::none());
    }

    public function pathExpression(string $layerRelativePath): string
    {
        return $this->provider->pathExpression($layerRelativePath);
    }

    /**
     * The short name of a class the provider imports, else its fully qualified name.
     */
    public function classReference(string $class): string
    {
        return $this->imports->aliasOf($class) ?? '\\'.$class;
    }
}
