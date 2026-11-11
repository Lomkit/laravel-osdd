<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Boundaries\BoundaryCheck;
use Lomkit\Osdd\Boundaries\BoundaryIgnoreList;
use Lomkit\Osdd\Boundaries\Formats\CheckFormat;
use Lomkit\Osdd\Boundaries\InvalidBoundaryIgnoreEntryException;
use Lomkit\Osdd\Layers\AmbiguousLayerNameException;
use Lomkit\Osdd\Layers\LayerNameList;
use Lomkit\Osdd\Layers\LayerNotFoundException;
use Lomkit\Osdd\Layers\LayersCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:check')]
class CheckCommand extends Command
{
    protected $signature = 'osdd:check
        {--layer=* : Only check these layers (full or package name, repeatable or comma separated)}
        {--format=text : Output format: text, json or github}
        {--transitive : Also allow the layers that the required layers require}';

    protected $description = 'Check that each OSDD layer only uses the layers it requires';

    public function handle(): int
    {
        $format = CheckFormat::tryFrom((string) $this->option('format'));

        if ($format === null) {
            $this->components->error('Unknown format ['.$this->option('format').']. Valid formats: '.implode(', ', CheckFormat::names()).'.');

            return self::FAILURE;
        }

        try {
            $layers = LayersCollection::fromConfig();
            $requested = LayerNameList::parse((array) $this->option('layer'));
            $selected = $requested === [] ? $layers : $layers->findAllByName($requested);
            $check = BoundaryCheck::of($layers, BoundaryIgnoreList::fromConfig(), ! (bool) $this->option('transitive'), $this->laravel->basePath());
        } catch (LayerNotFoundException|AmbiguousLayerNameException|InvalidBoundaryIgnoreEntryException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $report = $check->run($selected);
        $format->renderer()->render($report, $this->output);

        return $report->hasViolations() ? self::FAILURE : self::SUCCESS;
    }
}
