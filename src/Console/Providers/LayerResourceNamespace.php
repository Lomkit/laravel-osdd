<?php

namespace Lomkit\Osdd\Console\Providers;

use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;

final class LayerResourceNamespace
{
    private function __construct(
        private readonly string $package,
        private readonly ?string $explicit,
    ) {}

    /**
     * The package name, or vendor-package when another layer has the same package name.
     */
    public static function of(string $layerName, LayersCollection $layers): self
    {
        $package = Str::after($layerName, '/');
        $sharesPackageName = $layers->contains(
            fn (Layer $other): bool => $other->manifest->package() === $package && $other->manifest->name() !== $layerName,
        );

        return new self($package, $sharesPackageName ? str_replace('/', '-', $layerName) : null);
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
