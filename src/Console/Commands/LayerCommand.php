<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Concerns\ChoosesLayerGenerators;
use Lomkit\Osdd\Console\Concerns\OffersComposerUpdate;
use Lomkit\Osdd\Console\Concerns\RegistersLayerInComposer;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Console\Exceptions\MissingLayerNameException;
use Lomkit\Osdd\Console\Exceptions\UnknownLayerBucketException;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Layers\RefreshLayers;
use Lomkit\Osdd\Layers\RequiredLayers;
use Lomkit\Osdd\Scaffolding\LayerComposerJson;
use Lomkit\Osdd\Scaffolding\LayerGenerator;
use Lomkit\Osdd\Scaffolding\LayerGeneratorRunner;
use Lomkit\Osdd\Stubs\StubWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'osdd:layer')]
class LayerCommand extends Command
{
    use ChoosesLayerGenerators;
    use OffersComposerUpdate;
    use RegistersLayerInComposer;
    use ReportsOsddConsoleExceptions;

    protected $name = 'osdd:layer';

    protected $description = 'Create a new OSDD layer';

    public function __construct(protected Filesystem $files, private readonly StubWriter $stubs)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        [$name, $targetPath] = $this->layerNameAndTargetPath();
        $generators = $this->generators();
        $requiredLayers = RequiredLayers::resolve((array) $this->option('requires'), $name, $this->laravel->make(Layers::class));

        $layerPath = $targetPath.'/'.$name->package;

        if (! $this->option('force') && $this->files->isDirectory($layerPath) && ! $this->files->isEmptyDirectory($layerPath)) {
            $this->components->error("The layer directory [{$layerPath}] already exists. Pass --force to overwrite the files osdd:layer writes.");

            return self::FAILURE;
        }

        $this->generate($name, $layerPath, $generators, $requiredLayers);

        $this->components->info("Layer <options=bold>{$name->toString()}</> created at <options=bold>{$targetPath}</>.");

        return $this->offerComposerUpdate($name->toString()) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, InputArgument>
     */
    #[\Override]
    protected function getArguments(): array
    {
        return [new InputArgument('name', InputArgument::OPTIONAL, 'Layer name (vendor/package)')];
    }

    /**
     * @return array<int, InputOption>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('target-path', null, InputOption::VALUE_REQUIRED, 'Directory to create the layer in (default: the osdd.layers.paths entry named after the vendor)'),
            new InputOption('generators', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Generators to run, repeated or comma-separated: '.implode(', ', LayerGenerator::names())),
            new InputOption('requires', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Layers the new layer requires, repeated or comma-separated (full or package name)'),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Overwrite the files of an existing layer'),
            $this->composerUpdateOption(),
        ];
    }

    /**
     * @return array{LayerName, string}
     */
    private function layerNameAndTargetPath(): array
    {
        $argument = $this->argument('name');

        if (is_string($argument) && $argument !== '') {
            $name = LayerName::fromString($argument);

            return [$name, $this->targetPathFor($name)];
        }

        if (! $this->canPrompt()) {
            throw new MissingLayerNameException;
        }

        $vendor = $this->askForBucket();
        $package = text(
            label: 'Layer name',
            placeholder: 'my-layer',
            required: true,
            validate: fn (string $value): ?string => LayerName::validationError($vendor.'/'.$value),
        );
        $name = LayerName::fromString($vendor.'/'.$package);

        return [$name, $this->laravel->make(LayerBuckets::class)->path($vendor)];
    }

    private function targetPathFor(LayerName $name): string
    {
        $targetPath = $this->option('target-path');

        if (is_string($targetPath) && $targetPath !== '') {
            return $targetPath;
        }

        $buckets = $this->laravel->make(LayerBuckets::class);

        if (($path = $buckets->pathForVendor($name->vendor)) !== null) {
            return $path;
        }

        if (! $this->canPrompt()) {
            throw new UnknownLayerBucketException($name->vendor, $buckets->names());
        }

        return $buckets->path($this->askForBucket());
    }

    private function askForBucket(): string
    {
        $buckets = $this->laravel->make(LayerBuckets::class);

        return $buckets->onlyBucket() ?? (string) select(label: 'Where should the layer be created?', options: $buckets->names());
    }

    /**
     * @param  list<LayerGenerator>  $generators
     * @param  list<string>  $requiredLayers
     */
    private function generate(LayerName $name, string $layerPath, array $generators, array $requiredLayers): void
    {
        (new LayerComposerJson($this->files, $this->stubs))->write($name, $layerPath.'/composer.json', $requiredLayers);

        (new LayerGeneratorRunner(
            command: $this,
            stubs: $this->stubs,
            files: $this->files,
            name: $name,
            layerPath: $layerPath,
            generators: $generators,
            force: (bool) $this->option('force'),
            refreshLayers: $this->laravel->make(RefreshLayers::class),
        ))->run();

        $this->registerLayerInComposer($name->toString(), $layerPath);
    }
}
