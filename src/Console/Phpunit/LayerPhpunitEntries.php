<?php

namespace Lomkit\Osdd\Console\Phpunit;

use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerFolder;

final readonly class LayerPhpunitEntries
{
    private function __construct(
        public string $testSuite,
        public string $testsDirectory,
        public string $sourceDirectory,
    ) {}

    /**
     * The test suite and the coverage source osdd:phpunit writes for the layer, with paths relative to the project.
     */
    public static function of(Layer $layer, string $basePath): self
    {
        return new self(
            $layer->name->toString(),
            Path::relative($basePath, $layer->directory->path(LayerFolder::Tests)),
            Path::relative($basePath, $layer->sourceDirectory),
        );
    }

    public function hasTestSuiteIn(PhpunitXml $xml): bool
    {
        return $xml->hasTestSuite($this->testSuite);
    }

    public function hasSourceIn(PhpunitXml $xml): bool
    {
        return in_array($this->sourceDirectory, $xml->sourceIncludes(), true);
    }
}
