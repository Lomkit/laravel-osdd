<?php

namespace Lomkit\Osdd\Console\Start;

use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Filesystem\Path;

final class SkeletonCleaner
{
    /**
     * The SQLite database of a fresh Laravel app and the .gitignore that keeps it out of Git.
     */
    private const array KEPT_DATABASE_FILES = ['database.sqlite', '.gitignore'];

    private const array LEGACY_AUTOLOAD_NAMESPACES = ['Database\\Factories\\', 'Database\\Seeders\\'];

    private const string APP_DIRECTORY = 'app';

    private const string DEFAULT_APP_NAMESPACE = 'App\\';

    public function __construct(
        private readonly Filesystem $files,
        private readonly Factory $components,
        private readonly RootComposerJson $composer,
    ) {}

    public function clean(): void
    {
        $this->removeAppProvidersFromBootstrap();
        $this->deleteDirectory(self::APP_DIRECTORY);
        $this->deleteDirectory('config');
        $this->emptyDatabaseDirectory();
        $this->removeLegacyAutoload();
    }

    /**
     * Drop the providers of the deleted app/ directory; keep the package providers registered by hand.
     */
    private function removeAppProvidersFromBootstrap(): void
    {
        $path = $this->composer->basePath.'/bootstrap/providers.php';

        if (! $this->files->exists($path)) {
            return;
        }

        $namespaces = $this->appNamespaces();
        $providers = $this->files->getRequire($path);
        $appProviders = array_values(array_filter(
            is_array($providers) ? $providers : [],
            fn (mixed $provider): bool => is_string($provider) && Str::startsWith(ltrim($provider, '\\'), $namespaces),
        ));

        if ($appProviders === []) {
            return;
        }

        ServiceProvider::removeProviderFromBootstrapFile($appProviders, $path, strict: true);
        $this->components->info('Removed '.implode(', ', $appProviders).' from bootstrap/providers.php.');
    }

    /**
     * @return list<string>
     */
    private function appNamespaces(): array
    {
        $psr4 = $this->composer->psr4();
        $namespaces = array_keys(array_filter($psr4, fn (string $directory): bool => Path::normalize($directory) === self::APP_DIRECTORY));

        return $namespaces === [] ? [self::DEFAULT_APP_NAMESPACE] : $namespaces;
    }

    private function deleteDirectory(string $directory): void
    {
        $path = $this->composer->basePath.'/'.$directory;

        if (! $this->files->isDirectory($path)) {
            $this->components->warn("No {$directory}/ directory found, skipping.");

            return;
        }

        if (! $this->files->deleteDirectory($path)) {
            $this->components->error("Failed to delete {$directory}/ directory.");

            return;
        }

        $this->components->info("Deleted {$directory}/ directory.");
    }

    private function emptyDatabaseDirectory(): void
    {
        $path = $this->composer->basePath.'/database';

        if (! $this->files->exists($path.'/database.sqlite')) {
            $this->deleteDirectory('database');

            return;
        }

        foreach ($this->files->directories($path) as $directory) {
            if (is_string($directory)) {
                $this->files->deleteDirectory($directory);
            }
        }

        foreach ($this->files->files($path, true) as $file) {
            if (! in_array($file->getFilename(), self::KEPT_DATABASE_FILES, true)) {
                $this->files->delete($file->getPathname());
            }
        }

        $this->components->info('Emptied database/ directory, kept database/database.sqlite.');
    }

    private function removeLegacyAutoload(): void
    {
        if (! $this->composer->exists()) {
            $this->components->warn('No composer.json found, skipping autoload cleanup.');

            return;
        }

        $this->composer->removeAutoloadNamespaces(...self::LEGACY_AUTOLOAD_NAMESPACES);

        $this->components->info('Cleaned up legacy autoload entries from composer.json.');
    }
}
