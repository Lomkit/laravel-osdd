<?php

namespace Lomkit\Osdd\Console\Phpunit;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class PhpunitXml
{
    private bool $changed = false;

    private function __construct(
        private readonly DOMDocument $document,
        private readonly DOMXPath $xpath,
        private readonly string $directory,
        private readonly XmlIndentation $indentation,
        private readonly XmlFileLayout $layout,
    ) {}

    public static function load(string $path): self
    {
        $contents = (string) file_get_contents($path);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($contents);

        return new self($document, new DOMXPath($document), dirname($path), XmlIndentation::of($document), XmlFileLayout::of($contents));
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
        $suites = $this->first('//testsuites');

        if ($suites === null) {
            return;
        }

        $suite = $this->appendElement($suites, 'testsuite');
        $suite->setAttribute('name', $name);
        $this->appendElement($suite, 'directory', $directory);
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
            $this->indentation->remove($suite);
            $this->changed = true;
        }
    }

    /**
     * @return bool whether the directory was added
     */
    public function includeInSource(string $directory): bool
    {
        $root = $this->document->documentElement;

        if ($root === null || (new SourceCoverage($this->sourceIncludeDirectories()))->covers(DirectoryPattern::of($directory, $this->directory))) {
            return false;
        }

        $this->appendElement($this->sourceInclude($root), 'directory', $directory);
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
                $this->indentation->remove($element);
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
        return $this->layout->write($this->document);
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

    private function sourceInclude(DOMElement $root): DOMElement
    {
        $source = $this->first('//source') ?? $this->appendElement($root, 'source');

        return $this->first('//source/include') ?? $this->appendElement($source, 'include');
    }

    private function appendElement(DOMElement $parent, string $name, ?string $text = null): DOMElement
    {
        $element = $this->document->createElement($name);

        if ($text !== null) {
            $element->appendChild($this->document->createTextNode($text));
        }

        $this->indentation->append($parent, $element);

        return $element;
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
