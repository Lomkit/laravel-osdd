<?php

namespace Lomkit\Osdd\Graph\Formats;

enum GraphFormat: string
{
    case Mermaid = 'mermaid';
    case Dot = 'dot';
    case Json = 'json';

    public function renderer(): LayerGraphFormat
    {
        return match ($this) {
            self::Mermaid => new MermaidLayerGraph,
            self::Dot => new DotLayerGraph,
            self::Json => new JsonLayerGraph,
        };
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $format): string => $format->value, self::cases());
    }
}
