<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Inventory\InvalidManifestSummary;
use Lomkit\Osdd\Inventory\LayerSummary;
use Lomkit\Osdd\Inventory\RootProject;
use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\Layers;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:list')]
class ListCommand extends Command
{
    private const string NONE = '<fg=gray>none</>';

    protected $signature = 'osdd:list
        {--json : Output the layers as JSON}';

    protected $description = 'List the OSDD layers with their contents and health';

    public function handle(): int
    {
        $layers = $this->laravel->make(Layers::class);
        $project = RootProject::load($this->laravel->make(RootComposerJson::class), $this->laravel->make(Filesystem::class));
        $summaries = $this->summaries($layers, $project);
        $invalidManifests = array_map(
            fn (InvalidLayerManifestException $exception): InvalidManifestSummary => InvalidManifestSummary::of($exception, $project),
            $layers->invalidManifests(),
        );

        if ($this->option('json')) {
            $this->line((string) json_encode(
                [
                    'layers' => array_map(fn (LayerSummary $summary): array => $summary->toArray(), $summaries),
                    'invalid_manifests' => array_map(fn (InvalidManifestSummary $invalid): array => $invalid->toArray(), $invalidManifests),
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));

            return self::SUCCESS;
        }

        if ($summaries === [] && $invalidManifests === []) {
            $this->components->warn('No layer was discovered in the osdd.layers.paths directories.');

            return self::SUCCESS;
        }

        foreach ($summaries as $summary) {
            $this->display($summary);
        }

        foreach ($invalidManifests as $invalidManifest) {
            $this->displayInvalid($invalidManifest);
        }

        $this->components->info(count($summaries).' '.str('layer')->plural(count($summaries)).' listed.');

        if ($invalidManifests !== []) {
            $this->components->warn(count($invalidManifests).' invalid layer '.str('manifest')->plural(count($invalidManifests)).' skipped.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<LayerSummary>
     */
    private function summaries(Layers $layers, RootProject $project): array
    {
        return $layers->map(fn (Layer $layer): LayerSummary => LayerSummary::of($layer, $layers, $project));
    }

    private function display(LayerSummary $summary): void
    {
        $counts = $summary->counts;
        $problems = $summary->health->problems();

        $this->newLine();
        $this->components->twoColumnDetail("<fg=green;options=bold>{$summary->name}</>", $summary->path);
        $this->components->twoColumnDetail('Namespace', $summary->namespace);
        $this->components->twoColumnDetail('Providers', implode(', ', $summary->providers) ?: self::NONE);
        $this->components->twoColumnDetail('Requires', implode(', ', $summary->requires) ?: self::NONE);
        $this->components->twoColumnDetail('Models', (string) $counts->models);
        $this->components->twoColumnDetail('Migrations', (string) $counts->migrations);
        $this->components->twoColumnDetail('Seeders', (string) $counts->seeders);
        $this->components->twoColumnDetail('Tests', (string) $counts->tests);
        $this->components->twoColumnDetail('Route files', (string) $counts->routeFiles);
        $this->components->twoColumnDetail(
            'Health',
            $problems === [] ? '<fg=green;options=bold>OK</>' : '<fg=yellow>'.implode(', ', $problems).'</>',
        );
    }

    private function displayInvalid(InvalidManifestSummary $invalid): void
    {
        $this->newLine();
        $this->components->twoColumnDetail("<fg=red;options=bold>{$invalid->file}</>", '<fg=red;options=bold>INVALID</>');
        $this->components->twoColumnDetail('Error', $invalid->error);
    }
}
