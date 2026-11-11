<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\Layers;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'osdd:phpunit')]
class PhpunitCommand extends Command
{
    use ReportsOsddConsoleExceptions;

    protected $name = 'osdd:phpunit';

    protected $description = 'Synchronize phpunit.xml testsuites and coverage source with discovered OSDD layers';

    private const string LAYER_MIGRATIONS = '*/database/migrations';

    private const string ADDED = '<fg=green;options=bold>ADDED</>';

    private const string SKIPPED = '<fg=gray>SKIPPED</>';

    private const string REMOVED = '<fg=yellow;options=bold>REMOVED</>';

    public function handle(): int
    {
        $xmlPath = $this->laravel->basePath('phpunit.xml');

        if (! $this->laravel->make('files')->exists($xmlPath)) {
            $this->components->error("phpunit.xml not found at [{$xmlPath}].");

            return self::FAILURE;
        }

        $xml = PhpunitXml::load($xmlPath);

        if (! $xml->hasTestSuites()) {
            $this->components->error('phpunit.xml has no <testsuites> element.');

            return self::FAILURE;
        }

        $layers = $this->laravel->make(Layers::class)->failOnInvalidManifest();

        $this->excludeLayerMigrations($xml);

        if ($layers->isEmpty()) {
            $this->components->warn('No OSDD layers discovered.');
        }

        foreach ($layers as $layer) {
            $this->addTestSuite($xml, $layer);
            $this->includeSource($xml, $layer);
        }

        if ($this->option('prune')) {
            $this->prune($xml, $layers);
        }

        if (! $xml->isChanged()) {
            $this->components->info('phpunit.xml is already up to date.');

            return self::SUCCESS;
        }

        $this->laravel->make('files')->put($xmlPath, $xml->toXml());
        $this->components->info('phpunit.xml updated.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, InputOption>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('prune', null, InputOption::VALUE_NONE, 'Remove the test suites of removed layers and the source directories that no longer exist'),
        ];
    }

    private function excludeLayerMigrations(PhpunitXml $xml): void
    {
        $buckets = $this->laravel->make(LayerBuckets::class);

        foreach ($buckets->names() as $bucket) {
            $directory = $this->relative($buckets->path($bucket)).'/'.self::LAYER_MIGRATIONS;

            if ($xml->excludeFromSource($directory)) {
                $this->components->twoColumnDetail("Coverage exclusion <fg=gray>{$directory}</>", self::ADDED);
            }
        }
    }

    private function addTestSuite(PhpunitXml $xml, Layer $layer): void
    {
        $layerName = $layer->name->toString();

        if ($xml->hasTestSuite($layerName)) {
            $this->components->twoColumnDetail("Testsuite <fg=gray>{$layerName}</> already present", self::SKIPPED);

            return;
        }

        if (! is_dir($layer->directory->path(LayerFolder::Tests))) {
            $this->components->twoColumnDetail("Testsuite <fg=gray>{$layerName}</> has no tests/ directory", self::SKIPPED);

            return;
        }

        $directory = $this->relative($layer->directory->path(LayerFolder::Tests));
        $xml->addTestSuite($layerName, $directory);
        $this->components->twoColumnDetail("Testsuite <fg=gray>{$layerName}</> → {$directory}", self::ADDED);
    }

    private function includeSource(PhpunitXml $xml, Layer $layer): void
    {
        $source = $layer->sourceDirectory;

        if (is_dir($source) && $xml->includeInSource($directory = $this->relative($source))) {
            $this->components->twoColumnDetail("Coverage source <fg=gray>{$directory}</>", self::ADDED);
        }
    }

    private function prune(PhpunitXml $xml, Layers $layers): void
    {
        $layerNames = $layers->names();

        foreach ($xml->testSuiteNames() as $suite) {
            if (LayerName::validationError($suite) === null && ! in_array($suite, $layerNames, true)) {
                $xml->removeTestSuite($suite);
                $this->components->twoColumnDetail("Testsuite <fg=gray>{$suite}</>: the layer no longer exists", self::REMOVED);
            }
        }

        foreach ($xml->sourceIncludes() as $directory) {
            if (! is_dir(Path::isAbsolute($directory) ? $directory : $this->laravel->basePath($directory))) {
                $xml->removeSourceInclude($directory);
                $this->components->twoColumnDetail("Coverage source <fg=gray>{$directory}</>: the directory no longer exists", self::REMOVED);
            }
        }
    }

    private function relative(string $path): string
    {
        return Path::relative($this->laravel->basePath(), $path);
    }
}
