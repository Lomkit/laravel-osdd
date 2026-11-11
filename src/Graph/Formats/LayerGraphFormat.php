<?php

namespace Lomkit\Osdd\Graph\Formats;

use Lomkit\Osdd\Graph\LayerGraph;

interface LayerGraphFormat
{
    public function render(LayerGraph $graph): string;
}
