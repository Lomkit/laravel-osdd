<?php

namespace Lomkit\Osdd\Scaffolding;

use Lomkit\Osdd\Layers\Layers;

final readonly class ScaffoldContext
{
    /**
     * @param  list<string>  $selected  The keys of the scaffolds of this run
     */
    public function __construct(
        public LayerGenerators $generators,
        public DraftFiles $files,
        public Layers $layers,
        public ScaffoldConsole $console,
        private array $selected,
    ) {}

    public function has(string $key): bool
    {
        return in_array($key, $this->selected, true);
    }
}
