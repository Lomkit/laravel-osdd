<?php

namespace Lomkit\Osdd\Console\Phpunit;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class PhpunitXml
{
    private const string PHP_SUFFIX = '.php';

    private bool $changed = false;

    private function __construct(
        private readonly DOMDocument $document,
        private readonly DOMXPath $xpath,
        private readonly string $directory,
    ) {}

    public static function load(string $path): self
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->load($path);

        return new self($document, new DOMXPath($document), dirname($path));
    }

    public function hasTestSuites(): bool
    {
        return $this->first('//testsuites') !== null;
    }

    public function hasTestSuite(string $name): bool
    {
        return $this->testSuite($name) !== null;
    }

    public function addTestSuite(string $name, string $directory): void
    {
        $suite = $this->document->createElement('testsuite');
        $suite->setAttribute('name', $name);
        $suite->appendChild($this->document->createElement('directory', $directory));
        $this->first('//testsuites')?->appendChild($suite);
        $this->changed = true;
    }

    /**
     * @return list<string>
     */
    public function testSuiteNames(): array
    {
        return array_map(fn (DOMElement $suite): string => $suite->getAttribute('name'), $this->elements('//testsuites/testsuite'));
    }

    public function removeTestSuite(string $name): void
    {
        $suite = $this->testSuite($name);

        if ($suite !== null) {
            $suite->parentNode?->removeChild($suite);
            $this->changed = true;
        }
    }

    /**
     * @return bool whether the directory was added
     */
    public function includeInSource(string $directory): bool
    {
        if ((new SourceCoverage($this->sourceIncludeDirectories()))->covers(DirectoryPattern::of($directory, $this->directory))) {
            return false;
        }

        $element = $this->document->createElement('directory', $directory);
        $element->setAttribute('suffix', self::PHP_SUFFIX);
        $this->sourceInclude()->appendChild($element);
        $this->changed = true;

        return true;
    }

    /**
     * @return list<string>
     */
    public function sourceIncludes(): array
    {
        return array_map(fn (DOMElement $directory): string => trim($directory->textContent), $this->elements('//source/include/directory'));
    }

    /**
     * @return list<string>
     */
    public function staleSourceIncludes(): array
    {
        $stale = array_filter($this->sourceIncludeDirectories(), fn (SourceDirectory $entry): bool => $entry->isStale());

        return array_values(array_map(fn (SourceDirectory $entry): string => $entry->value, $stale));
    }

    public function removeSourceInclude(string $directory): void
    {
        foreach ($this->elements('//source/include/directory') as $element) {
            if (trim($element->textContent) === $directory) {
                $element->parentNode?->removeChild($element);
                $this->changed = true;
            }
        }
    }

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function toXml(): string
    {
        return (string) $this->document->saveXML();
    }

    /**
     * @return list<SourceDirectory>
     */
    private function sourceIncludeDirectories(): array
    {
        return array_map(
            fn (DOMElement $element): SourceDirectory => SourceDirectory::of($element, $this->directory),
            $this->elements('//source/include/directory'),
        );
    }

    private function sourceInclude(): DOMElement
    {
        $source = $this->first('//source');

        if ($source === null) {
            $source = $this->document->createElement('source');
            $this->document->documentElement?->appendChild($source);
        }

        $include = $this->first('//source/include');

        if ($include === null) {
            $include = $this->document->createElement('include');
            $source->appendChild($include);
        }

        return $include;
    }

    private function testSuite(string $name): ?DOMElement
    {
        foreach ($this->elements('//testsuites/testsuite') as $suite) {
            if ($suite->getAttribute('name') === $name) {
                return $suite;
            }
        }

        return null;
    }

    private function first(string $expression): ?DOMElement
    {
        return $this->elements($expression)[0] ?? null;
    }

    /**
     * @return list<DOMElement>
     */
    private function elements(string $expression): array
    {
        $elements = [];

        foreach ($this->xpath->query($expression) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }
}
