<?php

namespace Lomkit\Osdd\Tests\Seeders;

use Lomkit\Osdd\OsddServiceProvider;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Seeders\LayersSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\LoggingSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\OrdersLayerSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\SeedingLayerServiceProvider;
use Lomkit\Osdd\Tests\Seeders\Fixtures\UsersLayerSeeder;
use Lomkit\Osdd\Tests\TestCase;

class LayersSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LoggingSeeder::$runs = [];
    }

    protected function getPackageProviders($app): array
    {
        return [OsddServiceProvider::class, SeedingLayerServiceProvider::class];
    }

    public function test_it_runs_the_layer_seeders_in_priority_order(): void
    {
        $this->seed(LayersSeeder::class);

        $this->assertSame([UsersLayerSeeder::class, OrdersLayerSeeder::class], LoggingSeeder::$runs);
    }

    public function test_migrate_runs_it_as_the_seeder_option(): void
    {
        $this->artisan('migrate', ['--seed' => true, '--seeder' => LayersSeeder::class])->assertExitCode(0);

        $this->assertSame([UsersLayerSeeder::class, OrdersLayerSeeder::class], LoggingSeeder::$runs);
    }

    public function test_migrate_fresh_runs_it_as_the_seeder_option(): void
    {
        $this->artisan('migrate:fresh', ['--seed' => true, '--seeder' => LayersSeeder::class])->assertExitCode(0);

        $this->assertSame([UsersLayerSeeder::class, OrdersLayerSeeder::class], LoggingSeeder::$runs);
    }

    public function test_osdd_seed_runs_it(): void
    {
        $this->artisan('osdd:seed')
            ->expectsOutputToContain(UsersLayerSeeder::class)
            ->assertExitCode(0);

        $this->assertSame([UsersLayerSeeder::class, OrdersLayerSeeder::class], LoggingSeeder::$runs);
    }

    public function test_it_skips_a_seeder_class_that_does_not_exist(): void
    {
        $this->app->make(SeederRegistry::class)->push(5, 'Functional\\Missing\\Database\\Seeders\\MissingSeeder');

        $this->artisan('db:seed', ['--class' => LayersSeeder::class])
            ->expectsOutputToContain('Seeder [Functional\\Missing\\Database\\Seeders\\MissingSeeder] not found, skipping.')
            ->assertExitCode(0);

        $this->assertSame([UsersLayerSeeder::class, OrdersLayerSeeder::class], LoggingSeeder::$runs);
    }

    public function test_it_warns_when_no_layer_registers_a_seeder(): void
    {
        $this->app->instance(SeederRegistry::class, new SeederRegistry);

        $this->artisan('db:seed', ['--class' => LayersSeeder::class])
            ->expectsOutputToContain('No OSDD seeders registered.')
            ->assertExitCode(0);

        $this->assertSame([], LoggingSeeder::$runs);
    }
}
