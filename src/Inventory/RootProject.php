<?php

namespace Lomkit\Osdd\Inventory;

use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Support\Path;

final class RootProject
{
    /**
     * @param  list<string>  $requiredPackages
     * @param  list<string>  $pathRepositoryDirectories
     * @param  list<string>  $installedPackages
     * @param  list<string>  $testSuites
     */
    private function __construct(
        public readonly string $basePath,
        private readonly array $requiredPackages,
        private readonly array $pathRepositoryDirectories,
        private readonly array $installedPackages,
        private readonly array $testSuites,
    ) {}

    public static function load(string $basePath): self
    {
        $composer = self::decode($basePath.'/composer.json');

        return new self(
            $basePath,
            [...self::keys($composer['require'] ?? []), ...self::keys($composer['require-dev'] ?? [])],
            self::pathRepositoryDirectories($basePath, $composer['repositories'] ?? []),
            self::installedPackages($basePath.'/vendor/composer/installed.json'),
            is_file($basePath.'/phpunit.xml') ? PhpunitXml::load($basePath.'/phpunit.xml')->testSuiteNames() : [],
        );
    }

    public function requires(string $package): bool
    {
        return in_array($package, $this->requiredPackages, true);
    }

    public function hasPathRepositoryFor(string $directory): bool
    {
        return in_array(Path::normalize((string) realpath($directory)), $this->pathRepositoryDirectories, true);
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

    /**
     * @return array<array-key, mixed>
     */
    private static function decode(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private static function keys(mixed $packages): array
    {
        return is_array($packages) ? array_map(strval(...), array_keys($packages)) : [];
    }

    /**
     * @return list<string>
     */
    private static function pathRepositoryDirectories(string $basePath, mixed $repositories): array
    {
        $directories = [];

        foreach (is_array($repositories) ? $repositories : [] as $repository) {
            if (! is_array($repository) || ($repository['type'] ?? null) !== 'path' || ! is_string($repository['url'] ?? null)) {
                continue;
            }

            $pattern = Path::isAbsolute($repository['url']) ? $repository['url'] : $basePath.'/'.$repository['url'];

            foreach (glob($pattern, GLOB_ONLYDIR) ?: [] as $directory) {
                $directories[] = Path::normalize((string) realpath($directory));
            }
        }

        return $directories;
    }

    /**
     * @return list<string>
     */
    private static function installedPackages(string $path): array
    {
        $installed = self::decode($path);
        $packages = $installed['packages'] ?? $installed;

        return array_values(array_filter(
            array_map(fn (mixed $package): mixed => is_array($package) ? ($package['name'] ?? null) : null, is_array($packages) ? $packages : []),
            is_string(...),
        ));
    }
}
