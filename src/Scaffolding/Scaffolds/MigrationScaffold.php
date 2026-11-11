<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Commands\Make\MigrateMakeCommand;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;

final readonly class MigrationScaffold implements LayerScaffold
{
    public const string KEY = 'migration';

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
        $table = Str::snake(Str::pluralStudly($draft->studly()));

        if ($context->files->hasMatching(LayerFolder::Migrations, "*_create_{$table}_table.php")) {
            $context->console->warn("A create_{$table}_table migration already exists, skipping the migration generator.");

            return;
        }

        $context->generators->run(MigrateMakeCommand::class, ['name' => "create_{$table}_table", '--create' => $table]);
    }
}
