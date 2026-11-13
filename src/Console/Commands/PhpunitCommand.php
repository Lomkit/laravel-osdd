<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Console\Phpunit\LayerPhpunitEntries;
use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Layers\Layer;
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
            new InputOption('prune', null, InputOption::VALUE_NONE, 'Remove the test suites of removed layers and the source directories that no longer exist, keeping glob entries'),
        ];
    }

    private function addTestSuite(PhpunitXml $xml, Layer $layer): void
    {
        $entries = LayerPhpunitEntries::of($layer, $this->laravel->basePath());
        $layerName = $entries->testSuite;

        if ($xml->hasTestSuite($layerName)) {
            $this->components->twoColumnDetail("Testsuite <fg=gray>{$layerName}</> already present", self::SKIPPED);

            return;
        }

        if (! is_dir($layer->directory->path(LayerFolder::Tests))) {
            $this->components->twoColumnDetail("Testsuite <fg=gray>{$layerName}</> has no tests/ directory", self::SKIPPED);

            return;
        }

        $xml->addTestSuite($layerName, $entries->testsDirectory);
        $this->components->twoColumnDetail("Testsuite <fg=gray>{$layerName}</> → {$entries->testsDirectory}", self::ADDED);
    }

    private function includeSource(PhpunitXml $xml, Layer $layer): void
    {
        $directory = LayerPhpunitEntries::of($layer, $this->laravel->basePath())->sourceDirectory;

        if (is_dir($layer->sourceDirectory) && $xml->includeInSource($directory)) {
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

        foreach ($xml->staleSourceIncludes() as $directory) {
            $xml->removeSourceInclude($directory);
            $this->components->twoColumnDetail("Coverage source <fg=gray>{$directory}</>: the directory no longer exists", self::REMOVED);
        }
    }
}
