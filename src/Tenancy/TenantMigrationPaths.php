<?php

namespace Lomkit\Osdd\Tenancy;

use Lomkit\Osdd\Support\Path;

final class TenantMigrationPaths
{
    /**
     * @var list<string>
     */
    private array $paths = [];

    public function add(string ...$paths): void
    {
        foreach ($paths as $path) {
            $real = realpath($path);
            $absolute = Path::normalize($real === false ? $path : $real);

            if (! in_array($absolute, $this->paths, true)) {
                $this->paths[] = $absolute;
            }
        }
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->paths;
    }
}
