<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Console\Commands\Make\SeederMakeCommand;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;

final readonly class SeederScaffold implements LayerScaffold
{
    public const string KEY = 'seeder';

    #[\Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[\Override]
    public function dependsOn(): array
    {
        return [ProviderScaffold::KEY];
    }

    #[\Override]
    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void
    {
        $context->generators->run(SeederMakeCommand::class, ['name' => $draft->studly().'Seeder']);
    }
}
