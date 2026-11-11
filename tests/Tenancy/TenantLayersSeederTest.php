<?php

namespace Lomkit\Osdd\Tests\Tenancy;

use Lomkit\Osdd\OsddServiceProvider;
use Lomkit\Osdd\Seeders\TenantLayersSeeder;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;
use Lomkit\Osdd\Tests\Seeders\Fixtures\LoggingSeeder;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\BillingLayerServiceProvider;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\CentralAccountsSeeder;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\TenantInvoicesSeeder;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\TenantPlansSeeder;
use Lomkit\Osdd\Tests\TestCase;

class TenantLayersSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LoggingSeeder::$runs = [];
    }

    protected function getPackageProviders($app): array
    {
        return [OsddServiceProvider::class, BillingLayerServiceProvider::class];
    }

    public function test_it_runs_the_tenant_seeders_in_priority_order(): void
    {
        $this->artisan('db:seed', ['--class' => TenantLayersSeeder::class])->assertExitCode(0);

        $this->assertSame([TenantPlansSeeder::class, TenantInvoicesSeeder::class], LoggingSeeder::$runs);
    }

    public function test_osdd_seed_never_runs_the_tenant_seeders(): void
    {
        $this->artisan('osdd:seed')->assertExitCode(0);

        $this->assertSame([CentralAccountsSeeder::class], LoggingSeeder::$runs);
    }

    public function test_it_skips_a_tenant_seeder_class_that_does_not_exist(): void
    {
        $this->app->make(TenantSeederRegistry::class)->push(5, 'Functional\\Billing\\Database\\Seeders\\MissingSeeder');

        $this->artisan('db:seed', ['--class' => TenantLayersSeeder::class])
            ->expectsOutputToContain('Seeder [Functional\\Billing\\Database\\Seeders\\MissingSeeder] not found, skipping.')
            ->assertExitCode(0);

        $this->assertSame([TenantPlansSeeder::class, TenantInvoicesSeeder::class], LoggingSeeder::$runs);
    }

    public function test_it_warns_when_no_layer_registers_a_tenant_seeder(): void
    {
        $this->app->instance(TenantSeederRegistry::class, new TenantSeederRegistry);

        $this->artisan('db:seed', ['--class' => TenantLayersSeeder::class])
            ->expectsOutputToContain('No OSDD tenant seeders registered.')
            ->assertExitCode(0);

        $this->assertSame([], LoggingSeeder::$runs);
    }
}
