<?php

namespace Lomkit\Osdd\Console\Providers;

use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\Layers;

final class LayerResourceNamespace
{
    private function __construct(
        private readonly string $package,
        private readonly ?string $explicit,
    ) {}

    /**
     * The package name, or vendor-package when another layer has the same package name.
     */
    public static function of(LayerName $name, Layers $layers): self
    {
        return new self($name->package, $layers->sharingPackage($name)->isEmpty() ? null : $name->vendor.'-'.$name->package);
    }

    public function name(): string
    {
        return $this->explicit ?? $this->package;
    }

    public function isExplicit(): bool
    {
        return $this->explicit !== null;
    }

    public function argument(): string
    {
        return $this->explicit === null ? '' : ", '{$this->explicit}'";
    }
}
