<?php

namespace Lomkit\Osdd\Removal;

use Lomkit\Osdd\Composer\PathRepository;
use Lomkit\Osdd\Layers\Layer;

final readonly class LayerRemoval
{
    /**
     * @param  list<PathRepository>  $obsoleteRepositories  path repositories that cover the layer and no other package
     * @param  list<PathRepository>  $sharedRepositories  path repositories that cover the layer and other packages
     * @param  ?string  $testSuite  the layer test suite of phpunit.xml, null when there is none
     * @param  ?string  $coverageSource  the layer source directory in the phpunit.xml coverage source, null when absent
     */
    public function __construct(
        public Layer $layer,
        public bool $requiredByRoot,
        public array $obsoleteRepositories,
        public array $sharedRepositories,
        public string $phpunitFile,
        public ?string $testSuite,
        public ?string $coverageSource,
        public bool $deletesDirectory,
        public int $migrations,
    ) {}

    public function changesRootComposerJson(): bool
    {
        return $this->requiredByRoot || $this->obsoleteRepositories !== [];
    }

    public function changesPhpunitXml(): bool
    {
        return $this->testSuite !== null || $this->coverageSource !== null;
    }
}
