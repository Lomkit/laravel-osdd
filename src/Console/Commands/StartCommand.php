<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Console\Concerns\OffersComposerUpdate;
use Lomkit\Osdd\Console\Start\SkeletonCleaner;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Scaffolding\LayerBlueprint;
use Lomkit\Osdd\Scaffolding\LayerScaffolder;
use Lomkit\Osdd\Scaffolding\ScaffoldConsole;
use Lomkit\Osdd\Scaffolding\StartBlueprints;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'osdd:start')]
class StartCommand extends Command
{
    use OffersComposerUpdate;

    protected $name = 'osdd:start';

    protected $description = 'Prepare a fresh Laravel project for OSDD';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(LayerScaffolder $scaffolder): int
    {
        $blueprints = StartBlueprints::in($this->laravel->make(LayerBuckets::class), $this->laravel->basePath());

        foreach ($blueprints as $blueprint) {
            if ($this->files->exists($layerPath = $blueprint->draft->directory->root)) {
                $this->components->error("[{$layerPath}] already exists: osdd:start has already run on this project.");

                return self::FAILURE;
            }
        }

        if (! $this->confirmed()) {
            return self::FAILURE;
        }

        $console = new ScaffoldConsole($this->laravel->make(Kernel::class), $this->output, $this->input->isInteractive());

        foreach ($blueprints as $blueprint) {
            $scaffolder->scaffold($blueprint, $console);
        }

        (new SkeletonCleaner($this->files, $this->components, $this->laravel->make(RootComposerJson::class)))->clean();

        $this->components->info('Congratulations! Your project is ready for OSDD. Create your first layer with <options=bold>php artisan osdd:layer</>.');

        return $this->offerComposerUpdate(...array_map(
            fn (LayerBlueprint $blueprint): string => $blueprint->draft->name->toString(),
            $blueprints,
        )) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, InputOption>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('force', null, InputOption::VALUE_NONE, 'Delete app/, config/ and database/ without asking'),
            $this->composerUpdateOption(),
        ];
    }

    private function confirmed(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->canPrompt()) {
            $this->components->error('osdd:start deletes app/, config/ and database/. Pass --force to run it without interaction.');

            return false;
        }

        if (confirm('osdd:start deletes app/, config/ and database/ (database/database.sqlite is kept). Continue?', default: false)) {
            return true;
        }

        $this->components->warn('Nothing was changed.');

        return false;
    }
}
