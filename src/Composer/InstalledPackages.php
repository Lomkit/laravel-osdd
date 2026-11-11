<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Filesystem\Filesystem;

final class InstalledPackages
{
    private const string FILE = 'vendor/composer/installed.json';

    /**
     * @return list<string>
     */
    public static function of(Filesystem $files, string $basePath): array
    {
        $path = $basePath.'/'.self::FILE;
        $installed = $files->isFile($path) ? json_decode($files->get($path), true) : [];
        $packages = is_array($installed) ? ($installed['packages'] ?? $installed) : [];

        return array_values(array_filter(
            array_map(fn (mixed $package): mixed => is_array($package) ? ($package['name'] ?? null) : null, is_array($packages) ? $packages : []),
            is_string(...),
        ));
    }
}
