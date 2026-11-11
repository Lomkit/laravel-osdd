<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Console\Commands\Make\FactoryMakeCommand;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;

final readonly class FactoryScaffold implements LayerScaffold
{
    public const string KEY = 'factory';

    #[\Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[\Override]
    public function dependsOn(): array
    {
        return [ModelScaffold::KEY];
    }

    #[\Override]
    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void
    {
        if ($context->has(ModelScaffold::KEY)) {
            return;
        }

        $context->generators->run(FactoryMakeCommand::class, ['name' => $draft->singular().'Factory']);
    }
}
