<?php

namespace Lomkit\Osdd\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentCli;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\Layers;

final readonly class LayersPlugin implements Plugin
{
    private const string ID = 'osdd-layers';

    public function __construct(private Layers $layers) {}

    public static function make(): self
    {
        return app(self::class);
    }

    #[\Override]
    public function getId(): string
    {
        return self::ID;
    }

    #[\Override]
    public function register(Panel $panel): void
    {
        foreach ($this->layers->filter(fn (Layer $layer): bool => $layer->rootNamespace !== '') as $layer) {
            $location = LayerFilamentLocation::of($layer);
            $panelLocation = $location->forPanel($panel->getId());

            foreach (PanelComponents::cases() as $components) {
                $folder = $panelLocation->folder($components);
                $components->discoverIn($panel, $folder->directory, $folder->namespace);
            }

            FilamentCli::registerComponentLocation($location->directory, $location->namespace, LayerResourceNamespace::of($layer->name, $this->layers)->name());
        }
    }

    #[\Override]
    public function boot(Panel $panel): void {}
}
