<?php

namespace Lomkit\Osdd\Inventory;

use Lomkit\Osdd\Layers\Layer;
use Symfony\Component\Finder\Finder;

final readonly class LayerCounts
{
    private function __construct(
        public int $models,
        public int $migrations,
        public int $seeders,
        public int $tests,
        public int $routeFiles,
    ) {}

    public static function of(Layer $layer): self
    {
        return new self(
            models: self::count($layer->manifest->srcPath($layer->path).'/Models', '*.php'),
            migrations: self::count($layer->path.'/database/migrations', '*.php', depth: 0),
            seeders: self::count($layer->path.'/database/seeders', '*.php'),
            tests: self::count($layer->path.'/tests', '*Test.php'),
            routeFiles: self::count($layer->path.'/routes', '*.php', depth: 0),
        );
    }

    /**
     * @return array{models: int, migrations: int, seeders: int, tests: int, route_files: int}
     */
    public function toArray(): array
    {
        return [
            'models' => $this->models,
            'migrations' => $this->migrations,
            'seeders' => $this->seeders,
            'tests' => $this->tests,
            'route_files' => $this->routeFiles,
        ];
    }

    private static function count(string $directory, string $pattern, ?int $depth = null): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $finder = (new Finder)->files()->name($pattern)->in($directory);

        if ($depth !== null) {
            $finder->depth($depth);
        }

        return $finder->count();
    }
}
