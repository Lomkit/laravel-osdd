<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Graph\Formats\GraphFormat;
use Lomkit\Osdd\Graph\LayerGraph;
use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\Layers;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'osdd:graph')]
class GraphCommand extends Command
{
    protected $signature = 'osdd:graph
        {--format=mermaid : Output format: mermaid, dot or json}';

    protected $description = 'Print the graph of the OSDD layers and the layers they require';

    public function handle(): int
    {
        $format = GraphFormat::tryFrom((string) $this->option('format'));

        if ($format === null) {
            $this->components->error('Unknown format ['.$this->option('format').']. Valid formats: '.implode(', ', GraphFormat::names()).'.');

            return self::FAILURE;
        }

        try {
            $layers = $this->laravel->make(Layers::class)->failOnInvalidManifest();
        } catch (InvalidLayerManifestException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->output->writeln($format->renderer()->render(LayerGraph::of($layers)), OutputInterface::OUTPUT_RAW);

        return self::SUCCESS;
    }
}
