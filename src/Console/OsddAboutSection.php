<?php

namespace Lomkit\Osdd\Console;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayersCollection;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Support\Path;
use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;

class OsddAboutSection
{
    private const string NONE = 'none';

    public function __construct(
        private readonly Application $app,
        private readonly SeederRegistry $seeders,
        private readonly TenantMigrationPaths $tenantMigrationPaths,
        private readonly TenantSeederRegistry $tenantSeeders,
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

        /** @var Migrator $migrator */
        $migrator = $this->app->make('migrator');

        return [
            'Layers' => (string) count($layers),
            ...$layers,
            'Migration paths' => $this->listed($migrator->paths()),
            'Seeders' => (string) count($this->seeders->seeders()),
            'Tenant migration paths' => $this->listed($this->tenantMigrationPaths->all()),
            'Tenant seeders' => (string) count($this->tenantSeeders->seeders()),
        ];
    }

    /**
     * @param  array<array-key, string>  $paths
     */
    private function listed(array $paths): string
    {
        return $paths === [] ? self::NONE : implode(', ', array_map($this->relative(...), $paths));
    }

    private function relative(string $path): string
    {
        return Path::relative($this->app->basePath(), $path);
    }
}
