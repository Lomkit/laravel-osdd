<?php

namespace Lomkit\Osdd\Tests\Seeders;

use Illuminate\Support\Str;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Seeders\LayersSeeder;
use Lomkit\Osdd\Tests\Concerns\CreatesLayers;
use Lomkit\Osdd\Tests\Seeders\Fixtures\Billing\BillingSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\LoggingSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\Orders\OrdersSeeder;
use Lomkit\Osdd\Tests\Seeders\Fixtures\UsersLayerSeeder;
use Lomkit\Osdd\Tests\TestCase;

class SeedLayerOptionTest extends TestCase
{
    use CreatesLayers;

    private string $layersPath;

    protected function setUp(): void
    {
        parent::setUp();

        LoggingSeeder::$runs = [];
        $this->layersPath = sys_get_temp_dir().'/osdd-seed-layers-'.Str::random(8);
        $this->app['config']->set('osdd.layers.paths', [
            'functional' => $this->layersPath.'/functional',
            'technical' => $this->layersPath.'/technical',
        ]);

        $this->createLayer($this->layersPath.'/functional/orders', 'functional/orders', [
            'autoload' => ['psr-4' => ['Lomkit\\Osdd\\Tests\\Seeders\\Fixtures\\Orders\\' => 'src/']],
        ]);
        $this->createLayer($this->layersPath.'/functional/billing', 'functional/billing', [
            'autoload' => ['psr-4' => ['Lomkit\\Osdd\\Tests\\Seeders\\Fixtures\\Billing\\' => 'src/']],
        ]);
        $this->createLayer($this->layersPath.'/technical/archive', 'technical/archive', [
            'autoload' => ['psr-4' => ['Technical\\Archive\\' => 'src/']],
        ]);
        $this->createdLayerPaths[] = $this->layersPath;

        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(10, OrdersSeeder::class);
        $registry->push(0, BillingSeeder::class);
        $registry->push(5, UsersLayerSeeder::class);
    }

    protected function tearDown(): void
    {
        $this->deleteCreatedLayers();

        parent::tearDown();
    }

    public function test_it_seeds_only_the_given_layer(): void
    {
        $this->artisan('osdd:seed', ['--layer' => ['functional/orders']])->assertExitCode(0);

        $this->assertSame([OrdersSeeder::class], LoggingSeeder::$runs);
    }

    public function test_it_accepts_the_package_name(): void
    {
        $this->artisan('osdd:seed', ['--layer' => ['billing']])->assertExitCode(0);

        $this->assertSame([BillingSeeder::class], LoggingSeeder::$runs);
    }

    public function test_it_seeds_several_layers_in_priority_order(): void
    {
        $this->artisan('osdd:seed', ['--layer' => ['orders,billing']])->assertExitCode(0);
        $this->assertSame([BillingSeeder::class, OrdersSeeder::class], LoggingSeeder::$runs);

        LoggingSeeder::$runs = [];

        $this->artisan('osdd:seed', ['--layer' => ['orders', 'functional/billing', 'orders']])->assertExitCode(0);
        $this->assertSame([BillingSeeder::class, OrdersSeeder::class], LoggingSeeder::$runs);
    }

    public function test_an_unknown_layer_fails_before_anything_runs(): void
    {
        $this->artisan('osdd:seed', ['--layer' => ['oders'], '--fresh' => true])
            ->expectsOutputToContain('Layer [oders] not found.')
            ->doesntExpectOutputToContain('Dropping all tables')
            ->assertExitCode(1);

        $this->assertSame([], LoggingSeeder::$runs);
    }

    public function test_an_ambiguous_package_name_fails(): void
    {
        $this->createLayer($this->layersPath.'/technical/orders', 'technical/orders');

        $this->artisan('osdd:seed', ['--layer' => ['orders']])
            ->expectsOutputToContain('Layer [orders] is ambiguous')
            ->assertExitCode(1);

        $this->assertSame([], LoggingSeeder::$runs);
    }

    public function test_a_layer_without_seeders_warns(): void
    {
        $this->artisan('osdd:seed', ['--layer' => ['technical/archive']])
            ->expectsOutputToContain('No OSDD seeders registered for technical/archive.')
            ->assertExitCode(0);

        $this->assertSame([], LoggingSeeder::$runs);
    }

    public function test_the_layers_seeder_runs_every_seeder_again_afterwards(): void
    {
        $this->artisan('osdd:seed', ['--layer' => ['orders']])->assertExitCode(0);
        LoggingSeeder::$runs = [];

        $this->seed(LayersSeeder::class);

        $this->assertSame([BillingSeeder::class, UsersLayerSeeder::class, OrdersSeeder::class], LoggingSeeder::$runs);
    }
}
