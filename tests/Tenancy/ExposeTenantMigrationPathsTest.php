<?php

namespace Lomkit\Osdd\Tests\Tenancy;

use Lomkit\Osdd\Tenancy\ExposeTenantMigrationPaths;
use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use Lomkit\Osdd\Tests\TestCase;

class ExposeTenantMigrationPathsTest extends TestCase
{
    private const string TARGET = 'tenancy.migration_parameters.--path';

    private const string BILLING = '/srv/app/functional/billing/database/migrations/tenant';

    private const string ORDERS = '/srv/app/functional/orders/database/migrations/tenant';

    private const string APP_FOLDER = '/srv/app/database/migrations/tenant';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(TenantMigrationPaths::class)->add(self::BILLING, self::ORDERS);
    }

    public function test_it_leaves_the_config_untouched_when_no_key_is_set(): void
    {
        $this->app['config']->set('tenancy.migration_parameters', ['--path' => [self::APP_FOLDER], '--realpath' => true]);
        $before = $this->app['config']->all();

        $this->expose();

        $this->assertSame($before, $this->app['config']->all());
    }

    public function test_it_appends_the_tenant_paths_to_the_existing_values(): void
    {
        $this->app['config']->set('osdd.tenancy.expose_migration_paths_to', self::TARGET);
        $this->app['config']->set('tenancy.migration_parameters', ['--force' => true, '--path' => [self::APP_FOLDER], '--realpath' => true]);

        $this->expose();

        $this->assertSame(
            ['--force' => true, '--path' => [self::APP_FOLDER, self::BILLING, self::ORDERS], '--realpath' => true],
            $this->app['config']->get('tenancy.migration_parameters'),
        );
    }

    public function test_it_keeps_an_existing_single_path(): void
    {
        $this->app['config']->set('osdd.tenancy.expose_migration_paths_to', self::TARGET);
        $this->app['config']->set(self::TARGET, self::APP_FOLDER);

        $this->expose();

        $this->assertSame([self::APP_FOLDER, self::BILLING, self::ORDERS], $this->app['config']->get(self::TARGET));
    }

    public function test_it_creates_a_missing_key_as_a_list(): void
    {
        $this->app['config']->set('osdd.tenancy.expose_migration_paths_to', 'multitenancy.tenant_migration_paths');

        $this->expose();

        $this->assertSame([self::BILLING, self::ORDERS], $this->app['config']->get('multitenancy.tenant_migration_paths'));
    }

    public function test_exposing_twice_adds_no_duplicate(): void
    {
        $this->app['config']->set('osdd.tenancy.expose_migration_paths_to', self::TARGET);

        $this->expose();
        $this->expose();

        $this->assertSame([self::BILLING, self::ORDERS], $this->app['config']->get(self::TARGET));
    }

    public function test_a_cached_value_that_already_holds_the_paths_gets_no_duplicate(): void
    {
        $this->app['config']->set('osdd.tenancy.expose_migration_paths_to', self::TARGET);
        $this->app['config']->set(self::TARGET, [self::APP_FOLDER, self::BILLING, self::ORDERS]);

        $this->expose();

        $this->assertSame([self::APP_FOLDER, self::BILLING, self::ORDERS], $this->app['config']->get(self::TARGET));
    }

    private function expose(): void
    {
        $this->app->make(ExposeTenantMigrationPaths::class)();
    }
}
