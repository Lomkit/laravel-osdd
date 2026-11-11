<?php

namespace Lomkit\Osdd\Console\Commands;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\LayersCollection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:phpunit')]
class PhpunitCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:phpunit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize phpunit.xml testsuites with discovered OSDD layers';

    public function handle(): int
    {
        $xmlPath = $this->laravel->basePath('phpunit.xml');

        if (! $this->laravel->make('files')->exists($xmlPath)) {
            $this->error('phpunit.xml not found at '.$xmlPath);

            return self::FAILURE;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($xmlPath);

        $xpath = new DOMXPath($dom);
        $testSuites = $this->firstElement($xpath, '//testsuites');

        if (! $testSuites) {
            $this->error('<testsuites> element not found in phpunit.xml.');

            return self::FAILURE;
        }

        $changed = $this->excludeMigrationsFromCoverage($dom, $xpath);

        $layers = LayersCollection::fromConfig();

        if ($layers->isEmpty()) {
            $this->warn('No OSDD layers discovered.');

            if ($changed) {
                $this->laravel->make('files')->put($xmlPath, (string) $dom->saveXML());
                $this->info('phpunit.xml updated successfully.');
            }

            return self::SUCCESS;
        }

        $basePath = rtrim($this->laravel->basePath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $added = 0;

        foreach ($layers as $layer) {
            $layerName = $layer->manifest->name();

            $existing = $this->firstElement($xpath, '//testsuites/testsuite[@name="'.$layerName.'"]');

            if ($existing) {
                $this->line("Testsuite <comment>{$layerName}</comment> already present, skipping.");

                continue;
            }

            $testDir = $layer->path.'/tests';

            if (! is_dir($testDir)) {
                $this->line("Testsuite <comment>{$layerName}</comment> has no tests/ directory, skipping.");

                continue;
            }

            $relativeTestDir = Str::replaceFirst($basePath, '', $testDir);
            $relativeTestDir = str_replace('\\', '/', $relativeTestDir);

            $suite = $dom->createElement('testsuite');
            $suite->setAttribute('name', $layerName);
            $suite->appendChild($dom->createElement('directory', $relativeTestDir));
            $testSuites->appendChild($suite);

            $this->info("Added testsuite <comment>{$layerName}</comment> → {$relativeTestDir}");
            $added++;
        }

        if ($added > 0 || $changed) {
            $this->laravel->make('files')->put($xmlPath, (string) $dom->saveXML());
            $this->info('phpunit.xml updated successfully.');
        }

        return self::SUCCESS;
    }

    /**
     * Ensure layer migrations are excluded from code coverage.
     *
     * Every OSDD layer ships its own database/migrations directory; those files
     * are schema boilerplate and must not count towards coverage metrics.
     *
     * @return bool whether the document was modified
     */
    private function excludeMigrationsFromCoverage(DOMDocument $dom, DOMXPath $xpath): bool
    {
        $migrationsPath = '**/database/migrations';

        $alreadyExcluded = $this->firstElement($xpath, '//source/exclude/directory[normalize-space()="'.$migrationsPath.'"]');

        if ($alreadyExcluded) {
            return false;
        }

        $source = $this->firstElement($xpath, '//source');

        if (! $source) {
            $source = $dom->createElement('source');
            $dom->documentElement?->appendChild($source);
        }

        $exclude = $this->firstElement($xpath, '//source/exclude');

        if (! $exclude) {
            $exclude = $dom->createElement('exclude');
            $source->appendChild($exclude);
        }

        $directory = $dom->createElement('directory', $migrationsPath);
        $directory->setAttribute('suffix', '.php');
        $exclude->appendChild($directory);

        $this->info("Excluded <comment>{$migrationsPath}</comment> from code coverage.");

        return true;
    }

    private function firstElement(DOMXPath $xpath, string $expression): ?DOMElement
    {
        $node = $xpath->query($expression) ?: null;
        $first = $node?->item(0);

        return $first instanceof DOMElement ? $first : null;
    }
}
