<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Console\Commands\Make\PolicyMakeCommand;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;

final readonly class PolicyScaffold implements LayerScaffold
{
    public const string KEY = 'policy';

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
        $context->generators->run(PolicyMakeCommand::class, [
            'name' => $draft->studly().'Policy',
            '--model' => $context->has(ModelScaffold::KEY) ? $draft->singular() : null,
        ]);
    }
}
