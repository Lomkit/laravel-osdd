<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;
use Lomkit\Osdd\Tests\Concerns\CreatesProviderLayer;
use Lomkit\Osdd\Tests\TestCase;

class TenantOptionTest extends TestCase
{
    use CreatesProviderLayer;

    private const string BOOT_AND_REGISTER = <<<'PHP'
            public function boot(): void
            {
                //
            }

            public function register(): void
            {
                //
            }
        PHP;

    protected function tearDown(): void
    {
        $this->deleteProviderLayer();
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/migrations/tenant'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/tenant-migrations'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/seeders'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Models'));

        foreach (glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) ?: [] as $file) {
            $this->app['files']->delete($file);
        }

        parent::tearDown();
    }

    public function test_a_tenant_migration_is_written_into_the_tenant_folder(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_invoices_table', '--layer' => 'functional/test-layer', '--tenant' => true])
            ->assertExitCode(0);

        $this->assertCount(1, $this->migrationsIn('functional/test-layer/database/migrations/tenant', 'create_invoices_table'));
        $this->assertSame([], $this->migrationsIn('functional/test-layer/database/migrations', 'create_invoices_table'));
    }

    public function test_a_tenant_migration_follows_the_configured_folder(): void
    {
        $this->app['config']->set('osdd.tenancy.migrations', '/database/tenant-migrations/');

        $this->artisan('osdd:migration', ['name' => 'create_invoices_table', '--layer' => 'functional/test-layer', '--tenant' => true])
            ->expectsOutputToContain("\$this->loadTenantMigrationsFrom(__DIR__ . '/../../database/tenant-migrations');")
            ->assertExitCode(0);

        $this->assertCount(1, $this->migrationsIn('functional/test-layer/database/tenant-migrations', 'create_invoices_table'));
    }

    public function test_a_tenant_migration_reaches_the_tenant_paths_and_never_the_migrator(): void
    {
        $provider = $this->createProviderLayer(self::BOOT_AND_REGISTER);

        $this->artisan('osdd:migration', ['name' => 'create_invoices_table', '--layer' => self::PROVIDER_LAYER, '--tenant' => true])
            ->assertExitCode(0);

        $source = (string) file_get_contents($provider['path']);
        $this->assertStringContainsString("\$this->loadTenantMigrationsFrom(__DIR__ . '/../../database/migrations/tenant');", $source);
        $this->assertStringNotContainsString('loadMigrationsFrom', $source);

        $migratorPathsBefore = $this->app['migrator']->paths();
        $this->registerProvider($provider);

        $tenantFolder = (string) realpath($this->app->basePath(self::PROVIDER_LAYER.'/database/migrations/tenant'));
        $this->assertSame([$tenantFolder], $this->app->make(TenantMigrationPaths::class)->all());
        $this->assertSame($migratorPathsBefore, $this->app['migrator']->paths());
    }

    public function test_a_second_tenant_migration_does_not_repeat_the_registration(): void
    {
        $provider = $this->createProviderLayer(self::BOOT_AND_REGISTER);

        $this->artisan('osdd:migration', ['name' => 'create_invoices_table', '--layer' => self::PROVIDER_LAYER, '--tenant' => true]);
        $this->artisan('osdd:migration', ['name' => 'create_payments_table', '--layer' => self::PROVIDER_LAYER, '--tenant' => true]);

        $this->assertSame(1, substr_count((string) file_get_contents($provider['path']), 'loadTenantMigrationsFrom('));
    }

    public function test_a_central_migration_is_registered_as_before(): void
    {
        $provider = $this->createProviderLayer(self::BOOT_AND_REGISTER);
        $original = (string) file_get_contents($provider['path']);

        $this->artisan('osdd:migration', ['name' => 'create_accounts_table', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $expected = str_replace(
            "    public function boot(): void\n    {\n        //\n    }",
            "    public function boot(): void\n    {\n        \$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');\n    }",
            $original,
        );
        $this->assertSame($expected, file_get_contents($provider['path']));
        $this->assertCount(1, $this->migrationsIn(self::PROVIDER_LAYER.'/database/migrations', 'create_accounts_table'));
        $this->assertFalse(is_dir($this->app->basePath(self::PROVIDER_LAYER.'/database/migrations/tenant')));
    }

    public function test_a_tenant_migration_without_provider_prints_the_line_to_add(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_invoices_table', '--layer' => 'functional/test-layer', '--tenant' => true])
            ->expectsOutputToContain('has no service provider')
            ->expectsOutputToContain("\$this->loadTenantMigrationsFrom(__DIR__ . '/../../database/migrations/tenant');")
            ->assertExitCode(0);
    }

    public function test_a_tenant_seeder_is_registered_for_the_tenants_only(): void
    {
        $provider = $this->createProviderLayer(self::BOOT_AND_REGISTER);

        $this->artisan('osdd:seeder', ['name' => 'InvoicesSeeder', '--layer' => self::PROVIDER_LAYER, '--tenant' => true])
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath(self::PROVIDER_LAYER.'/database/seeders/InvoicesSeeder.php'));
        $this->assertStringContainsString(
            '$this->loadTenantSeeders([\Functional\ProviderLayer\Database\Seeders\InvoicesSeeder::class]);',
            (string) file_get_contents($provider['path']),
        );

        $this->registerProvider($provider);

        $this->assertSame(['Functional\ProviderLayer\Database\Seeders\InvoicesSeeder'], $this->app->make(TenantSeederRegistry::class)->seeders());
        $this->assertSame([], $this->app->make(SeederRegistry::class)->seeders());
    }

    public function test_a_tenant_seeder_without_provider_prints_the_line_to_add(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'InvoicesSeeder', '--layer' => 'functional/test-layer', '--tenant' => true])
            ->expectsOutputToContain('$this->loadTenantSeeders([\Functional\TestLayer\Database\Seeders\InvoicesSeeder::class]);')
            ->assertExitCode(0);
    }

    public function test_a_tenant_model_creates_a_tenant_migration_and_a_tenant_seeder(): void
    {
        $provider = $this->createProviderLayer(self::BOOT_AND_REGISTER);

        $this->artisan('osdd:model', ['name' => 'Invoice', '--layer' => self::PROVIDER_LAYER, '--migration' => true, '--seed' => true, '--tenant' => true])
            ->assertExitCode(0);

        $this->assertCount(1, $this->migrationsIn(self::PROVIDER_LAYER.'/database/migrations/tenant', 'create_invoices_table'));
        $this->assertSame([], $this->migrationsIn(self::PROVIDER_LAYER.'/database/migrations', 'create_invoices_table'));

        $this->registerProvider($provider);

        $this->assertSame(
            [(string) realpath($this->app->basePath(self::PROVIDER_LAYER.'/database/migrations/tenant'))],
            $this->app->make(TenantMigrationPaths::class)->all(),
        );
        $this->assertSame(['Functional\ProviderLayer\Database\Seeders\InvoiceSeeder'], $this->app->make(TenantSeederRegistry::class)->seeders());
        $this->assertSame([], $this->app->make(SeederRegistry::class)->seeders());
        $this->assertStringNotContainsString('loadMigrationsFrom', (string) file_get_contents($provider['path']));
    }

    public function test_a_model_without_tenant_keeps_central_companions(): void
    {
        $provider = $this->createProviderLayer(self::BOOT_AND_REGISTER);

        $this->artisan('osdd:model', ['name' => 'Account', '--layer' => self::PROVIDER_LAYER, '--migration' => true, '--seed' => true])
            ->assertExitCode(0);

        $this->assertCount(1, $this->migrationsIn(self::PROVIDER_LAYER.'/database/migrations', 'create_accounts_table'));

        $this->registerProvider($provider);

        $this->assertSame([], $this->app->make(TenantMigrationPaths::class)->all());
        $this->assertSame([], $this->app->make(TenantSeederRegistry::class)->seeders());
        $this->assertSame(['Functional\ProviderLayer\Database\Seeders\AccountSeeder'], $this->app->make(SeederRegistry::class)->seeders());
    }

    /**
     * @return list<string>
     */
    private function migrationsIn(string $folder, string $name): array
    {
        return glob($this->app->basePath($folder.'/*_'.$name.'.php')) ?: [];
    }

    /**
     * @param  array{class: class-string, path: string}  $provider
     */
    private function registerProvider(array $provider): void
    {
        require_once $provider['path'];
        $this->app->register($provider['class']);
    }
}
