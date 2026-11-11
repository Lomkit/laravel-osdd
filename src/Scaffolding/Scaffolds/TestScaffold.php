<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Console\Commands\Make\TestMakeCommand;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;

final readonly class TestScaffold implements LayerScaffold
{
    public const string KEY = 'test';

    #[\Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[\Override]
    public function dependsOn(): array
    {
        return [];
    }

    #[\Override]
    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void
    {
        $context->generators->run(TestMakeCommand::class, ['name' => $draft->studly().'Test']);
    }
}
