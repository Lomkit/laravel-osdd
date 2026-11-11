<?php

namespace Lomkit\Osdd\Filament;

use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\Layer;

final readonly class LayerFilamentLocation
{
    private const string FOLDER = 'Filament';

    private function __construct(
        public string $namespace,
        public string $directory,
    ) {}

    public static function of(Layer $layer): self
    {
        return new self($layer->rootNamespace.self::FOLDER, $layer->sourceDirectory.'/'.self::FOLDER);
    }

    public function forPanel(string $panelId): self
    {
        $panel = Str::studly($panelId);

        return new self($this->namespace.'\\'.$panel, $this->directory.'/'.$panel);
    }

    public function folder(PanelComponents $components): self
    {
        return new self($this->namespace.'\\'.$components->value, $this->directory.'/'.$components->value);
    }
}
