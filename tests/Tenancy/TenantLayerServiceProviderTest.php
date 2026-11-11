<?php

namespace Lomkit\Osdd\Tests\Tenancy;

use Illuminate\Database\Seeder;
use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\TenantInvoicesSeeder;
use Lomkit\Osdd\Tests\TestCase;

class TenantLayerServiceProviderTest extends TestCase
{
    public function test_tenant_migrations_reach_the_tenant_paths_and_not_the_migrator(): void
    {
        $tenantFolder = __DIR__.'/Fixtures/database/migrations/tenant';

        $this->makeProvider()->callLoadTenantMigrationsFrom($tenantFolder);

        $this->assertSame([(string) realpath($tenantFolder)], $this->app->make(TenantMigrationPaths::class)->all());
        $this->assertNotContains($tenantFolder, $this->app['migrator']->paths());
        $this->assertNotContains((string) realpath($tenantFolder), $this->app['migrator']->paths());
    }

    public function test_tenant_seeders_stay_out_of_the_central_registry(): void
    {
        $this->makeProvider()->callLoadTenantSeeders([TenantInvoicesSeeder::class], 5);

        $this->assertSame([TenantInvoicesSeeder::class], $this->app->make(TenantSeederRegistry::class)->seeders());
        $this->assertSame([], $this->app->make(SeederRegistry::class)->seeders());
    }

    private function makeProvider(): LayerServiceProvider
    {
        return new class($this->app) extends LayerServiceProvider
        {
            public function callLoadTenantMigrationsFrom(string ...$paths): void
            {
                $this->loadTenantMigrationsFrom(...$paths);
            }

            /**
             * @param  list<class-string<Seeder>>  $seeders
             */
            public function callLoadTenantSeeders(array $seeders, int $priority): void
            {
                $this->loadTenantSeeders($seeders, $priority);
            }
        };
    }
}
