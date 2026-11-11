<?php

namespace Lomkit\Osdd\Console;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Support\Path;

class OsddAboutSection
{
    private const string NONE = 'none';

    public function __construct(
        private readonly Application $app,
        private readonly SeederRegistry $seeders,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $layers = LayersCollection::fromConfig()
            ->toBase()
            ->mapWithKeys(fn (Layer $layer): array => [$layer->manifest->name() => $this->relative($layer->path)])
            ->all();

        return [
            'Layers' => (string) count($layers),
            ...$layers,
            'Migration paths' => $this->migrationPaths(),
            'Seeders' => (string) count($this->seeders->seeders()),
        ];
    }

    private function migrationPaths(): string
    {
        /** @var Migrator $migrator */
        $migrator = $this->app->make('migrator');

        $paths = array_map($this->relative(...), $migrator->paths());

        return $paths === [] ? self::NONE : implode(', ', $paths);
    }

    private function relative(string $path): string
    {
        return Path::relative($this->app->basePath(), $path);
    }
}
