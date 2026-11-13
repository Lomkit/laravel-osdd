<?php

namespace Lomkit\Osdd\Console\Phpunit;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final readonly class XmlIndentation
{
    private const string DEFAULT_LEVEL = '    ';

    private const string NEWLINE = "\n";

    private function __construct(
        private DOMDocument $document,
        private string $level,
    ) {}

    public static function of(DOMDocument $document): self
    {
        $root = $document->documentElement;
        $firstChild = $root === null ? null : self::elementChildren($root)[0] ?? null;
        $level = $firstChild === null ? null : self::indentationOf($firstChild);

        return new self($document, $level === null || $level === '' ? self::DEFAULT_LEVEL : $level);
    }

    public function append(DOMElement $parent, DOMElement $element): void
    {
        $siblings = self::elementChildren($parent);

        if ($siblings === []) {
            $this->appendToEmpty($parent, $element);

            return;
        }

        $lastSibling = $siblings[count($siblings) - 1];
        $indentation = self::indentationOf($lastSibling);
        $parent->insertBefore($element, $lastSibling->nextSibling);

        if ($indentation !== null) {
            $parent->insertBefore($this->document->createTextNode(self::NEWLINE.$indentation), $element);
        }
    }

    public function remove(DOMElement $element): void
    {
        $previous = $element->previousSibling;

        if ($previous instanceof DOMText && trim($previous->data) === '') {
            $previous->parentNode?->removeChild($previous);
        }

        $element->parentNode?->removeChild($element);
    }

    private function appendToEmpty(DOMElement $parent, DOMElement $element): void
    {
        $indentation = self::indentationOf($parent) ?? '';

        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMText && trim($child->data) === '') {
                $parent->removeChild($child);
            }
        }

        $parent->appendChild($this->document->createTextNode(self::NEWLINE.$indentation.$this->level));
        $parent->appendChild($element);
        $parent->appendChild($this->document->createTextNode(self::NEWLINE.$indentation));
    }

    private static function indentationOf(DOMNode $node): ?string
    {
        $previous = $node->previousSibling;

        if (! $previous instanceof DOMText || trim($previous->data) !== '' || ! str_contains($previous->data, self::NEWLINE)) {
            return null;
        }

        return substr($previous->data, strrpos($previous->data, self::NEWLINE) + 1);
    }

    /**
     * @return list<DOMElement>
     */
    private static function elementChildren(DOMElement $parent): array
    {
        $children = [];

        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        return $children;
    }
}
