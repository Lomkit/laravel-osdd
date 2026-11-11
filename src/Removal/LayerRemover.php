<?php

namespace Lomkit\Osdd\Removal;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\MalformedComposerJsonException;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Layers\RefreshLayers;

final readonly class LayerRemover
{
    public function __construct(
        private Filesystem $files,
        private RootComposerJson $root,
        private RefreshLayers $refreshLayers,
    ) {}

    /**
     * @throws MalformedComposerJsonException
     */
    public function remove(LayerRemoval $removal): void
    {
        if ($removal->changesRootComposerJson()) {
            $this->root->forgetLayer($removal->layer->name, ...$removal->obsoleteRepositories);
        }

        if ($removal->changesPhpunitXml()) {
            $this->forgetPhpunitEntries($removal);
        }

        if ($removal->deletesDirectory) {
            $this->files->deleteDirectory($removal->layer->directory->root);
        }

        ($this->refreshLayers)();
    }

    private function forgetPhpunitEntries(LayerRemoval $removal): void
    {
        $xml = PhpunitXml::load($removal->phpunitFile);

        if ($removal->testSuite !== null) {
            $xml->removeTestSuite($removal->testSuite);
        }

        if ($removal->coverageSource !== null) {
            $xml->removeSourceInclude($removal->coverageSource);
        }

        $this->files->put($removal->phpunitFile, $xml->toXml());
    }
}
