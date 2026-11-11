<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Concerns\OffersComposerUpdate;
use Lomkit\Osdd\Console\Concerns\RegistersLayerInComposer;
use Lomkit\Osdd\Console\StubWriter;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'osdd:layer')]
class LayerCommand extends Command
{
    use OffersComposerUpdate;
    use RegistersLayerInComposer;

    protected $signature = 'osdd:layer
        {name? : Layer name (vendor/package)}
        {--target-path= : Full path to the target directory (skips selection prompt)}
        {--generators=* : Generators to run (skips selection prompt)}';

    protected $description = 'Create a new OSDD layer';

    private const array GENERATORS = [
        'migration',
        'model',
        'factory',
        'seeder',
        'service-provider',
        'test',
        'controller',
        'policy',
        'routes',
    ];

    private const array DEFAULT_GENERATORS = ['migration', 'model', 'factory', 'service-provider', 'test'];

    public function __construct(protected Filesystem $files, private readonly StubWriter $stubs)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($name = $this->argument('name')) {
            $targetPath = $this->option('target-path') ?? $this->askForTargetPath();
        } else {
            [$vendor, $targetPath] = $this->askForVendorAndPath();
            $package = $this->askForPackage();
            $name = $vendor.'/'.$package;
        }

        $generators = $this->option('generators') ?: $this->askForGenerators();

        $this->generate($name, $targetPath, $generators);

        $this->components->info("Layer <options=bold>{$name}</> created at <options=bold>{$targetPath}</>.");

        $this->offerComposerUpdate($name);

        return self::SUCCESS;
    }

    /**
     * @return array{string, string}
     */
    private function askForVendorAndPath(): array
    {
        /** @var array<string, string> $paths */
        $paths = config('osdd.layers.paths');

        if (count($paths) === 1) {
            return [(string) array_key_first($paths), (string) reset($paths)];
        }

        $key = (string) select(
            label: 'Where should the layer be created?',
            options: array_keys($paths),
        );

        return [$key, $paths[$key]];
    }

    private function askForTargetPath(): string
    {
        return $this->askForVendorAndPath()[1];
    }

    private function askForPackage(): string
    {
        return text(
            label: 'Layer name',
            placeholder: 'my-layer',
            required: true,
            validate: fn (string $value) => preg_match('/^[a-z0-9-]+$/', $value)
                ? null
                : 'Name must use lowercase letters, numbers and hyphens.',
        );
    }

    /**
     * @return array<int|string>
     */
    private function askForGenerators(): array
    {
        return multiselect(
            label: 'Which generators should be run?',
            options: self::GENERATORS,
            default: self::DEFAULT_GENERATORS,
        );
    }

    /**
     * @param  array<int|string|null>  $generators
     */
    private function generate(string $name, string $targetPath, array $generators): void
    {
        [$vendor, $package] = explode('/', $name);

        $layerPath = $targetPath.'/'.$package;
        $namespace = $this->toNamespace($vendor, $package);
        $singular = Str::singular(Str::studly($package));
        $pascal = Str::studly($package);
        $pluralSnake = Str::snake(Str::pluralStudly($pascal));

        // Create composer.json first — layer must be discoverable before make commands run
        $this->stubs->write(
            'layer/composer',
            $layerPath.'/composer.json',
            [
                '{{ name }}' => $name,
                '{{ namespace }}' => str_replace('\\', '\\\\', $namespace),
            ],
        );

        $withFactory = in_array('factory', $generators);
        $withModel = in_array('model', $generators);

        // When model is also generated, --factory on osdd:model handles factory creation.
        // Remove it from the loop so we don't duplicate it.
        $effectiveGenerators = ($withFactory && $withModel)
            ? array_diff($generators, ['factory'])
            : $generators;

        foreach ($effectiveGenerators as $generator) {
            match ($generator) {
                'migration' => $this->call('osdd:migration', ['name' => "create_{$pluralSnake}_table", '--create' => $pluralSnake, '--layer' => $name]),
                'model' => $this->call('osdd:model', array_filter(['name' => $singular, '--layer' => $name, '--factory' => $withFactory ?: null])),
                'factory' => $this->call('osdd:factory', ['name' => "{$singular}Factory", '--layer' => $name]),
                'seeder' => $this->call('osdd:seeder', ['name' => "{$pascal}Seeder", '--layer' => $name]),
                'service-provider' => $this->generateServiceProvider($layerPath.'/src/Providers', $namespace, $package, $layerPath),
                'test' => $this->call('osdd:test', ['name' => "{$pascal}Test", '--layer' => $name]),
                'controller' => $this->call('osdd:controller', ['name' => "{$pascal}Controller", '--layer' => $name]),
                'policy' => $this->call('osdd:policy', array_filter(['name' => "{$pascal}Policy", '--layer' => $name, '--model' => $withModel ? $singular : null])),
                'routes' => $this->generateRoutes($layerPath),
                default => null,
            };
        }

        $this->registerLayerInComposer($name, $layerPath);
    }

    private function generateRoutes(string $layerPath): void
    {
        foreach (['web', 'api', 'console', 'channels'] as $file) {
            $this->stubs->write('layer/routes/'.$file, $layerPath.'/routes/'.$file.'.php');
        }
    }

    private function generateServiceProvider(string $path, string $namespace, string $package, string $layerPath): void
    {
        $serviceProviderClass = $this->toServiceProviderClass($package);

        $this->stubs->write(
            'layer/service-provider',
            $path.'/'.$serviceProviderClass.'.php',
            [
                '{{ namespace }}' => $namespace,
                '{{ class }}' => $serviceProviderClass,
                '{{ seederClass }}' => $this->toSeederClass($package),
            ],
        );

        $this->injectProviderInComposerJson(
            $layerPath.'/composer.json',
            $namespace.'\\Providers\\'.$serviceProviderClass,
        );
    }

    private function toNamespace(string $vendor, string $package): string
    {
        return Str::pascal($vendor).'\\'.Str::pascal($package);
    }

    private function toServiceProviderClass(string $package): string
    {
        return Str::pascal($package).'ServiceProvider';
    }

    private function toSeederClass(string $package): string
    {
        return Str::pascal($package).'Seeder';
    }
}
