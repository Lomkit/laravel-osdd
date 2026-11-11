<?php

namespace Lomkit\Osdd\Inventory;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\InstalledPackages;
use Lomkit\Osdd\Composer\MalformedComposerJsonException;
use Lomkit\Osdd\Composer\PathRepository;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Filesystem\Path;

final class RootProject
{
    /**
     * @param  list<string>  $requiredPackages
     * @param  list<PathRepository>  $pathRepositories
     * @param  list<string>  $installedPackages
     * @param  list<string>  $testSuites
     */
    private function __construct(
        public readonly string $basePath,
        private readonly array $requiredPackages,
        private readonly array $pathRepositories,
        private readonly array $installedPackages,
        private readonly array $testSuites,
    ) {}

    /**
     * A root composer.json that cannot be read counts as one that requires nothing.
     */
    public static function load(RootComposerJson $composer, Filesystem $files): self
    {
        $basePath = $composer->basePath;

        try {
            $requiredPackages = $composer->requiredPackages();
            $pathRepositories = $composer->pathRepositories();
        } catch (MalformedComposerJsonException) {
            $requiredPackages = $pathRepositories = [];
        }

        return new self(
            $basePath,
            $requiredPackages,
            $pathRepositories,
            InstalledPackages::of($files, $basePath),
            $files->isFile($basePath.'/phpunit.xml') ? PhpunitXml::load($basePath.'/phpunit.xml')->testSuiteNames() : [],
        );
    }

    public function requires(string $package): bool
    {
        return in_array($package, $this->requiredPackages, true);
    }

    public function hasPathRepositoryFor(string $directory): bool
    {
        foreach ($this->pathRepositories as $repository) {
            if ($repository->covers($directory)) {
                return true;
            }
        }

        return false;
    }

    public function hasInstalled(string $package): bool
    {
        return in_array($package, $this->installedPackages, true);
    }

    public function hasTestSuite(string $name): bool
    {
        return in_array($name, $this->testSuites, true);
    }

    public function relativePath(string $path): string
    {
        return Path::relative($this->basePath, $path);
    }
}
