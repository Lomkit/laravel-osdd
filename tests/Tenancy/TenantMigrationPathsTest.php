<?php

namespace Lomkit\Osdd\Tests\Tenancy;

use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use PHPUnit\Framework\TestCase;

class TenantMigrationPathsTest extends TestCase
{
    public function test_it_keeps_paths_absolute_normalised_unique_and_in_registration_order(): void
    {
        $tenantFolder = __DIR__.'/Fixtures/database/migrations/tenant';
        $paths = new TenantMigrationPaths;

        $paths->add(__DIR__.'/Fixtures/database/migrations/../migrations/tenant', '/srv/billing/database/migrations/tenant');
        $paths->add($tenantFolder, '/srv/orders/./database/migrations/tenant/');

        $this->assertSame([
            str_replace('\\', '/', (string) realpath($tenantFolder)),
            '/srv/billing/database/migrations/tenant',
            '/srv/orders/database/migrations/tenant',
        ], $paths->all());
    }

    public function test_it_starts_empty(): void
    {
        $this->assertSame([], (new TenantMigrationPaths)->all());
    }
}
