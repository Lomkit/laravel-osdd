<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;
use Lomkit\Osdd\Stubs\Stub;

final readonly class StubScaffold implements LayerScaffold
{
    /**
     * @param  string  $relativePath  The file to write, relative to the layer root; it is also the scaffold key
     */
    public function __construct(
        private Stub $stub,
        private string $relativePath,
    ) {}

    #[\Override]
    public function key(): string
    {
        return $this->relativePath;
    }

    #[\Override]
    public function dependsOn(): array
    {
        return [];
    }

    #[\Override]
    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void
    {
        $context->files->write($this->stub, $this->relativePath);
    }
}
