<?php

namespace Lomkit\Osdd\Tests\Tenancy;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\OsddServiceProvider;
use Lomkit\Osdd\Tests\Seeders\Fixtures\LoggingSeeder;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\BillingLayerServiceProvider;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\CentralAccountsSeeder;
use Lomkit\Osdd\Tests\TestCase;

class TenantMigrationsIntegrationTest extends TestCase
{
    private string $databases;

    protected function getPackageProviders($app): array
    {
        return [OsddServiceProvider::class, BillingLayerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->databases = sys_get_temp_dir().'/osdd-tenancy-'.uniqid();
        mkdir($this->databases);
        touch($this->databases.'/central.sqlite');
        touch($this->databases.'/tenant.sqlite');

        $app['config']->set('database.default', 'central');
        $app['config']->set('database.connections.central', ['driver' => 'sqlite', 'database' => $this->databases.'/central.sqlite', 'prefix' => '']);
        $app['config']->set('database.connections.tenant', ['driver' => 'sqlite', 'database' => $this->databases.'/tenant.sqlite', 'prefix' => '']);
        $app['config']->set('osdd.tenancy.expose_migration_paths_to', 'tenancy.migration_parameters.--path');
        $app['config']->set('tenancy.migration_parameters', [
            '--force' => true,
            '--path' => [$app->databasePath('migrations/tenant')],
            '--realpath' => true,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem)->deleteDirectory($this->databases);
    }

    public function test_tenant_tables_land_on_the_tenant_connection_only(): void
    {
        $this->artisan('migrate', ['--database' => 'central'])->assertExitCode(0);

        /** @var array<string, mixed> $tenantParameters */
        $tenantParameters = config('tenancy.migration_parameters');
        $this->assertSame(0, Artisan::call('migrate', $tenantParameters + ['--database' => 'tenant']));

        $this->assertTrue(Schema::connection('central')->hasTable('central_accounts'));
        $this->assertFalse(Schema::connection('central')->hasTable('tenant_invoices'));
        $this->assertTrue(Schema::connection('tenant')->hasTable('tenant_invoices'));
        $this->assertFalse(Schema::connection('tenant')->hasTable('central_accounts'));
    }

    public function test_the_migrator_never_holds_a_tenant_path(): void
    {
        $tenantFolder = (string) realpath(__DIR__.'/Fixtures/database/migrations/tenant');

        $this->assertSame(
            [$this->app->databasePath('migrations/tenant'), str_replace('\\', '/', $tenantFolder)],
            config('tenancy.migration_parameters.--path'),
        );
        $this->assertNotContains($tenantFolder, array_map(fn (string $path): string => (string) realpath($path), $this->app['migrator']->paths()));
    }

    public function test_migrate_fresh_on_the_central_connection_creates_no_tenant_table(): void
    {
        $this->artisan('migrate:fresh', ['--database' => 'central'])->assertExitCode(0);

        $this->assertTrue(Schema::connection('central')->hasTable('central_accounts'));
        $this->assertFalse(Schema::connection('central')->hasTable('tenant_invoices'));
    }

    public function test_osdd_seed_runs_the_central_seeders_only(): void
    {
        LoggingSeeder::$runs = [];

        $this->artisan('osdd:seed', ['--database' => 'central'])->assertExitCode(0);

        $this->assertSame([CentralAccountsSeeder::class], LoggingSeeder::$runs);
    }
}
