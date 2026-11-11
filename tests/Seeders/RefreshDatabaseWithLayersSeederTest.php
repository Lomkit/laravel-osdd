<?php

namespace Lomkit\Osdd\Tests\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lomkit\Osdd\OsddServiceProvider;
use Lomkit\Osdd\Seeders\LayersSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\LoggingSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\OrdersLayerSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\SeedingLayerServiceProvider;
use Lomkit\Osdd\Tests\Seeders\Fixtures\UsersLayerSeeder;
use Lomkit\Osdd\Tests\TestCase;

class RefreshDatabaseWithLayersSeederTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = LayersSeeder::class;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        LoggingSeeder::$runs = [];
    }

    protected function getPackageProviders($app): array
    {
        return [OsddServiceProvider::class, SeedingLayerServiceProvider::class];
    }

    public function test_refresh_database_seeds_the_layers(): void
    {
        $this->assertSame([UsersLayerSeeder::class, OrdersLayerSeeder::class], LoggingSeeder::$runs);
    }
}
