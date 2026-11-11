<?php

namespace Lomkit\Osdd\Scaffolding;

final readonly class LayerBlueprint
{
    /**
     * @param  list<LayerScaffold>  $scaffolds
     */
    public function __construct(
        public LayerDraft $draft,
        public array $scaffolds,
    ) {}

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(fn (LayerScaffold $scaffold): string => $scaffold->key(), $this->scaffolds);
    }
}
