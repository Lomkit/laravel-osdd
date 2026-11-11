<?php

namespace Lomkit\Osdd\Graph\Formats;

use Lomkit\Osdd\Graph\LayerGraph;

final class DotLayerGraph implements LayerGraphFormat
{
    private const string INDENT = '    ';

    #[\Override]
    public function render(LayerGraph $graph): string
    {
        $lines = ['digraph layers {', self::INDENT.'rankdir=LR;'];

        foreach ($graph->names() as $layer) {
            $lines[] = self::INDENT.'"'.$layer.'";';
        }

        foreach ($graph->names() as $layer) {
            foreach ($graph->requires($layer) as $requirement) {
                $lines[] = self::INDENT.'"'.$layer.'" -> "'.$requirement.'";';
            }
        }

        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }
}
