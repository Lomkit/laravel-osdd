<?php

namespace Lomkit\Osdd\Tests\Tenancy\Fixtures;

use Lomkit\Osdd\LayerServiceProvider;

class BillingLayerServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadTenantMigrationsFrom(__DIR__.'/database/migrations/tenant');

        $this->loadSeeders([CentralAccountsSeeder::class]);
        $this->loadTenantSeeders([TenantInvoicesSeeder::class], 10);
        $this->loadTenantSeeders([TenantPlansSeeder::class]);
    }
}
