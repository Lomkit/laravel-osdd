<?php

namespace Lomkit\Osdd\Filament;

use Filament\Panel;

enum PanelComponents: string
{
    case Resources = 'Resources';
    case Pages = 'Pages';
    case Widgets = 'Widgets';
    case Clusters = 'Clusters';

    public function discoverIn(Panel $panel, string $directory, string $namespace): void
    {
        match ($this) {
            self::Resources => $panel->discoverResources(in: $directory, for: $namespace),
            self::Pages => $panel->discoverPages(in: $directory, for: $namespace),
            self::Widgets => $panel->discoverWidgets(in: $directory, for: $namespace),
            self::Clusters => $panel->discoverClusters(in: $directory, for: $namespace),
        };
    }
}
