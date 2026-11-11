<?php

namespace Lomkit\Osdd\Composer;

enum ComposerUpdateReason
{
    case LayersAdded;
    case LayerRemoved;

    public function optionDescription(): string
    {
        return match ($this) {
            self::LayersAdded => 'Run composer update for the new layers (asked when interactive, skipped otherwise)',
            self::LayerRemoved => 'Run composer update for the removed layer (asked when interactive, skipped otherwise)',
        };
    }

    public function skippedHint(string $command): string
    {
        return match ($this) {
            self::LayersAdded => "Run <options=bold>{$command}</> to install the new layers.",
            self::LayerRemoved => "Run <options=bold>{$command}</> now to uninstall the layer.",
        };
    }
}
