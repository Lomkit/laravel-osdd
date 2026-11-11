<?php

namespace Lomkit\Osdd\Removal;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\MalformedComposerJsonException;
use Lomkit\Osdd\Composer\PathRepository;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Console\Phpunit\LayerPhpunitEntries;
use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Graph\LayerGraph;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\Layers;

final readonly class LayerRemovalPlanner
{
    private const string PHPUNIT_FILE = 'phpunit.xml';

    private const string MANIFEST_FILE = 'composer.json';

    public function __construct(
        private Filesystem $files,
        private RootComposerJson $root,
    ) {}

    /**
     * @throws LayerStillRequiredException
     * @throws MalformedComposerJsonException
     */
    public function plan(Layer $layer, Layers $layers, bool $keepFiles): LayerRemoval
    {
        $name = $layer->name->toString();
        $dependents = LayerGraph::of($layers)->dependentsOf($name);

        if ($dependents !== []) {
            throw new LayerStillRequiredException($name, $dependents);
        }

        $repositories = array_values(array_filter(
            $this->root->pathRepositories(),
            fn (PathRepository $repository): bool => $repository->covers($layer->directory->root),
        ));
        $shared = array_values(array_filter($repositories, fn (PathRepository $repository): bool => $this->coversAnotherPackage($repository, $layer)));
        $phpunitFile = $this->root->basePath.'/'.self::PHPUNIT_FILE;
        $phpunit = $this->files->exists($phpunitFile) ? PhpunitXml::load($phpunitFile) : null;
        $entries = LayerPhpunitEntries::of($layer, $this->root->basePath);

        return new LayerRemoval(
            layer: $layer,
            requiredByRoot: in_array($name, $this->root->requiredPackages(), true),
            obsoleteRepositories: array_values(array_filter($repositories, fn (PathRepository $repository): bool => ! in_array($repository, $shared, true))),
            sharedRepositories: $shared,
            phpunitFile: $phpunitFile,
            testSuite: $phpunit !== null && $entries->hasTestSuiteIn($phpunit) ? $entries->testSuite : null,
            coverageSource: $phpunit !== null && $entries->hasSourceIn($phpunit) ? $entries->sourceDirectory : null,
            deletesDirectory: ! $keepFiles,
            migrations: count($this->files->glob($layer->directory->path(LayerFolder::Migrations).'/*.php')),
        );
    }

    private function coversAnotherPackage(PathRepository $repository, Layer $layer): bool
    {
        $layerDirectory = Path::normalize((string) realpath($layer->directory->root));

        foreach ($repository->coveredDirectories() as $directory) {
            if ($directory !== $layerDirectory && $this->files->exists($directory.'/'.self::MANIFEST_FILE)) {
                return true;
            }
        }

        return false;
    }
}
