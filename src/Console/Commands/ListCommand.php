<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Inventory\LayerSummary;
use Lomkit\Osdd\Inventory\RootProject;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;
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
        $summaries = $this->summaries();

        if ($this->option('json')) {
            $this->line((string) json_encode(
                ['layers' => array_map(fn (LayerSummary $summary): array => $summary->toArray(), $summaries)],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ));

            return self::SUCCESS;
        }

        if ($summaries === []) {
            $this->components->warn('No layer was discovered in the osdd.layers.paths directories.');

            return self::SUCCESS;
        }

        foreach ($summaries as $summary) {
            $this->display($summary);
        }

        $this->components->info(count($summaries).' '.str('layer')->plural(count($summaries)).' listed.');

        return self::SUCCESS;
    }

    /**
     * @return list<LayerSummary>
     */
    private function summaries(): array
    {
        $layers = LayersCollection::fromConfig()->sortBy(fn (Layer $layer): string => $layer->manifest->name());
        $project = RootProject::load($this->laravel->basePath());

        return array_values($layers->toBase()->map(
            fn (Layer $layer): LayerSummary => LayerSummary::of($layer, $layers, $project),
        )->all());
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
}
