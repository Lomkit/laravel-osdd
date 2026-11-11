<?php

namespace Lomkit\Osdd\Graph\Formats;

use Lomkit\Osdd\Graph\LayerGraph;

final class MermaidLayerGraph implements LayerGraphFormat
{
    private const string INDENT = '    ';

    #[\Override]
    public function render(LayerGraph $graph): string
    {
        $lines = ['graph LR'];

        foreach ($graph->names() as $layer) {
            $lines[] = self::INDENT.$this->id($layer).'["'.$layer.'"]';
        }

        foreach ($graph->names() as $layer) {
            foreach ($graph->requires($layer) as $requirement) {
                $lines[] = self::INDENT.$this->id($layer).' --> '.$this->id($requirement);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function id(string $layer): string
    {
        return (string) preg_replace('/[^A-Za-z0-9_]/', '_', $layer);
    }
}
