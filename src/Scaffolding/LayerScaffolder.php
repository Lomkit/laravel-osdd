<?php

namespace Lomkit\Osdd\Scaffolding;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\LayerComposerJson;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Layers\RefreshLayers;
use Lomkit\Osdd\Stubs\StubWriter;

final readonly class LayerScaffolder
{
    public function __construct(
        private LayerComposerJson $composerJson,
        private Filesystem $files,
        private StubWriter $stubs,
        private RefreshLayers $refreshLayers,
        private RootComposerJson $rootComposer,
    ) {}

    /**
     * Write the layer composer.json, run the scaffolds in dependency order, then require the layer in the root composer.json.
     */
    public function scaffold(LayerBlueprint $blueprint, ScaffoldConsole $console, bool $force = false): void
    {
        $draft = $blueprint->draft;
        $steps = ScaffoldPlan::of($blueprint->scaffolds)->steps();

        $this->composerJson->write($draft->manifest, $draft->directory->manifestFile());

        $context = new ScaffoldContext(
            new LayerGenerators($console, $draft->name, $force),
            new DraftFiles($this->files, $this->stubs, $this->composerJson, $this->refreshLayers, $draft->directory),
            ($this->refreshLayers)(),
            $console,
            $blueprint->keys(),
        );

        foreach ($steps as $scaffold) {
            $scaffold->scaffold($draft, $context);
        }

        $this->requireInRootComposer($draft, $console);

        $console->info("Layer <options=bold>{$draft->name->toString()}</> created at <options=bold>{$draft->directory->root}</>.");
    }

    private function requireInRootComposer(LayerDraft $draft, ScaffoldConsole $console): void
    {
        if (! $this->rootComposer->exists()) {
            $console->warn('No composer.json found at project root, skipping Composer registration.');

            return;
        }

        $this->rootComposer->requireLayer($draft->name, $draft->directory);

        $name = $draft->name->toString();
        $console->info("Registered <options=bold>{$name}</> in composer.json. Run <options=bold>composer update {$name}</> to install it.");
    }
}
