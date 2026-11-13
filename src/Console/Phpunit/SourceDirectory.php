<?php

namespace Lomkit\Osdd\Console\Phpunit;

use DOMElement;

final readonly class SourceDirectory
{
    private const string DEFAULT_SUFFIX = '.php';

    private function __construct(
        public string $value,
        public DirectoryPattern $pattern,
        private string $prefix,
        private string $suffix,
    ) {}

    /**
     * A <directory> entry of <source>; an empty prefix or suffix means PHPUnit's default, as in PHPUnit.
     */
    public static function of(DOMElement $element, string $phpunitDirectory): self
    {
        $value = trim($element->textContent);

        return new self(
            $value,
            DirectoryPattern::of($value, $phpunitDirectory),
            $element->getAttribute('prefix'),
            $element->getAttribute('suffix') ?: self::DEFAULT_SUFFIX,
        );
    }

    /**
     * The folders whose .php files PHPUnit reads through this entry: none when a prefix or another suffix filters them.
     *
     * @return list<string>
     */
    public function coveredFolders(): array
    {
        return $this->prefix === '' && $this->suffix === self::DEFAULT_SUFFIX ? $this->pattern->folders() : [];
    }

    /**
     * A literal entry that resolves to no folder. A glob entry is never stale: it may match folders created later.
     */
    public function isStale(): bool
    {
        return ! $this->pattern->hasGlob() && $this->pattern->folders() === [];
    }
}
