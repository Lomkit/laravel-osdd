<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Lomkit\Osdd\Composer\ComposerUpdateReason;
use Lomkit\Osdd\Composer\PathRepository;
use Lomkit\Osdd\Console\Concerns\OffersComposerUpdate;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Removal\LayerRemoval;
use Lomkit\Osdd\Removal\LayerRemovalPlanner;
use Lomkit\Osdd\Removal\LayerRemover;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'osdd:remove')]
final class RemoveCommand extends Command
{
    use OffersComposerUpdate;
    use ReportsOsddConsoleExceptions;

    protected $name = 'osdd:remove';

    protected $description = 'Remove an OSDD layer: its root composer.json entries, its phpunit.xml entries and its directory';

    private const string REMOVE = '<fg=yellow;options=bold>REMOVE</>';

    private const string KEEP = '<fg=gray>KEEP</>';

    public function handle(LayerRemovalPlanner $planner, LayerRemover $remover): int
    {
        $layers = $this->laravel->make(Layers::class)->failOnInvalidManifest();
        $removal = $planner->plan($layers->find((string) $this->argument('name')), $layers, (bool) $this->option('keep-files'));
        $name = $removal->layer->name->toString();

        $this->summarize($removal);

        if (! $this->confirmed($name)) {
            return self::FAILURE;
        }

        $remover->remove($removal);
        $this->components->info("Layer [{$name}] removed.");

        if ($removal->migrations > 0) {
            $this->components->warn("The tables created by the migrations of [{$name}] stay in the database: drop them with a migration of another layer if they are no longer needed.");
        }

        return $this->offerComposerUpdate(ComposerUpdateReason::LayerRemoved, $name) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, InputArgument>
     */
    #[\Override]
    protected function getArguments(): array
    {
        return [new InputArgument('name', InputArgument::REQUIRED, 'Layer to remove (full or package name)')];
    }

    /**
     * @return array<int, InputOption>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('keep-files', null, InputOption::VALUE_NONE, 'Keep the layer directory'),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Remove the layer without asking'),
            $this->composerUpdateOption(ComposerUpdateReason::LayerRemoved),
        ];
    }

    private function summarize(LayerRemoval $removal): void
    {
        $name = $removal->layer->name->toString();
        $this->components->info("Removing layer [{$name}]:");

        if ($removal->requiredByRoot) {
            $this->components->twoColumnDetail("composer.json: require <fg=gray>{$name}</>", self::REMOVE);
        }

        foreach ($removal->obsoleteRepositories as $repository) {
            $this->components->twoColumnDetail($this->repositoryLabel($repository), self::REMOVE);
        }

        foreach ($removal->sharedRepositories as $repository) {
            $this->components->twoColumnDetail($this->repositoryLabel($repository).' (still covers other packages)', self::KEEP);
        }

        if ($removal->testSuite !== null) {
            $this->components->twoColumnDetail("phpunit.xml: test suite <fg=gray>{$removal->testSuite}</>", self::REMOVE);
        }

        if ($removal->coverageSource !== null) {
            $this->components->twoColumnDetail("phpunit.xml: coverage source <fg=gray>{$removal->coverageSource}</>", self::REMOVE);
        }

        $directory = $removal->layer->directory->relativeTo($this->laravel->basePath());
        $this->components->twoColumnDetail("Directory <fg=gray>{$directory}</>", $removal->deletesDirectory ? '<fg=red;options=bold>DELETE</>' : self::KEEP);

        if ($removal->migrations > 0) {
            $this->components->twoColumnDetail('Database tables of its '.$removal->migrations.' '.Str::plural('migration', $removal->migrations), self::KEEP);
        }
    }

    private function repositoryLabel(PathRepository $repository): string
    {
        return "composer.json: path repository <fg=gray>{$repository->url}</>";
    }

    private function confirmed(string $name): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->canPrompt()) {
            $this->components->error("Nothing was removed. Pass --force to remove [{$name}] without interaction.");

            return false;
        }

        if (confirm("Remove the layer [{$name}]?", default: false)) {
            return true;
        }

        $this->components->warn('Nothing was removed.');

        return false;
    }
}
