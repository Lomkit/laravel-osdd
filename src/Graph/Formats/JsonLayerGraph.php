<?php

namespace Lomkit\Osdd\Graph\Formats;

use Lomkit\Osdd\Graph\LayerGraph;

final class JsonLayerGraph implements LayerGraphFormat
{
    #[\Override]
    public function render(LayerGraph $graph): string
    {
        $edges = [];

        foreach ($graph->names() as $layer) {
            foreach ($graph->requires($layer) as $requirement) {
                $edges[] = ['from' => $layer, 'to' => $requirement];
            }
        }

        return json_encode(
            ['nodes' => $graph->names(), 'edges' => $edges],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
}
