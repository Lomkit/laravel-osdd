<?php

namespace Lomkit\Osdd\Tests\Console;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;
use Lomkit\Osdd\Tests\TestCase;

class OsddAboutSectionTest extends TestCase
{
    public function test_it_lists_the_layers_the_migration_paths_and_the_seeder_count(): void
    {
        $this->app->instance('migrator', $this->emptyMigrator());
        $this->app->make('migrator')->path($this->app->basePath('functional/test-layer/database/migrations'));
        $this->app->make(SeederRegistry::class)->push(0, 'Functional\\TestLayer\\Database\\Seeders\\TestLayerSeeder', 'Functional\\TestLayer\\Database\\Seeders\\DemoSeeder');

        $this->assertSame([
            'layers' => '1',
            'functional/test-layer' => 'functional/test-layer',
            'migration_paths' => 'functional/test-layer/database/migrations',
            'seeders' => '2',
            'tenant_migration_paths' => 'none',
            'tenant_seeders' => '0',
        ], $this->aboutOsdd());
    }

    public function test_it_lists_the_tenant_migration_paths_and_the_tenant_seeder_count(): void
    {
        $this->app->instance('migrator', $this->emptyMigrator());
        $this->app->make(TenantMigrationPaths::class)->add(
            $this->app->basePath('functional/test-layer/database/migrations/tenant'),
            $this->app->basePath('functional/billing/database/migrations/tenant'),
        );
        $this->app->make(TenantSeederRegistry::class)->push(0, 'Functional\\TestLayer\\Database\\Seeders\\TenantPlansSeeder');

        $about = $this->aboutOsdd();

        $this->assertSame('functional/test-layer/database/migrations/tenant, functional/billing/database/migrations/tenant', $about['tenant_migration_paths']);
        $this->assertSame('1', $about['tenant_seeders']);
        $this->assertSame('none', $about['migration_paths']);
        $this->assertSame('0', $about['seeders']);
    }

    public function test_it_reports_an_application_without_layers(): void
    {
        $this->app['config']->set('osdd.layers.paths', []);
        $this->app->instance('migrator', $this->emptyMigrator());

        $this->assertSame([
            'layers' => '0',
            'migration_paths' => 'none',
            'seeders' => '0',
            'tenant_migration_paths' => 'none',
            'tenant_seeders' => '0',
        ], $this->aboutOsdd());
    }

    public function test_it_shows_an_osdd_section(): void
    {
        $this->artisan('about', ['--only' => 'osdd'])
            ->expectsOutputToContain('OSDD')
            ->expectsOutputToContain('functional/test-layer')
            ->assertExitCode(0);
    }

    /**
     * @return array<string, string>
     */
    private function aboutOsdd(): array
    {
        Artisan::call('about', ['--only' => 'osdd', '--json' => true]);

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)['o_s_d_d'];
    }

    private function emptyMigrator(): Migrator
    {
        return new Migrator($this->app['migration.repository'], $this->app['db'], $this->app['files']);
    }
}
