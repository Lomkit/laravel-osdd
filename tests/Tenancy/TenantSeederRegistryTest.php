<?php

namespace Lomkit\Osdd\Tests\Tenancy;

use Lomkit\Osdd\Tenancy\TenantSeederRegistry;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\TenantInvoicesSeeder;
use Lomkit\Osdd\Tests\Tenancy\Fixtures\TenantPlansSeeder;
use PHPUnit\Framework\TestCase;

class TenantSeederRegistryTest extends TestCase
{
    public function test_it_orders_by_priority_and_keeps_registration_order_on_ties(): void
    {
        $registry = new TenantSeederRegistry;

        $registry->push(10, TenantInvoicesSeeder::class);
        $registry->push(0, TenantPlansSeeder::class);

        $this->assertSame([TenantPlansSeeder::class, TenantInvoicesSeeder::class], $registry->seeders());
    }

    public function test_a_seeder_pushed_twice_keeps_its_first_priority(): void
    {
        $registry = new TenantSeederRegistry;

        $registry->push(0, TenantInvoicesSeeder::class);
        $registry->push(20, TenantPlansSeeder::class);
        $registry->push(30, '\\'.TenantInvoicesSeeder::class);

        $this->assertSame([TenantInvoicesSeeder::class, TenantPlansSeeder::class], $registry->seeders());
    }
}
