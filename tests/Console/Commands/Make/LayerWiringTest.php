<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Contracts\Console\Kernel;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;

class LayerWiringTest extends TestCase
{
    public function test_a_generated_view_resolves_under_the_layer_namespace(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:view', ['name' => 'orders/index', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        file_put_contents($this->layerPath('resources/views/orders/index.blade.php'), 'Orders: {{ $count }}');
        $provider->register($this->app);

        $this->assertSame('Orders: 3', view('provider-layer::orders.index', ['count' => 3])->render());
    }

    public function test_a_second_view_does_not_register_the_views_twice(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:view', ['name' => 'index', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:view', ['name' => 'show', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame(1, substr_count($provider->source(), 'withViews('));
    }

    public function test_an_existing_views_call_with_another_namespace_is_kept(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod("\$this->withViews(__DIR__ . '/../../resources/views', 'shop');"));

        $this->artisan('osdd:view', ['name' => 'index', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        file_put_contents($this->layerPath('resources/views/index.blade.php'), 'Shop');
        $provider->register($this->app);

        $this->assertSame(1, substr_count($provider->source(), 'withViews('));
        $this->assertSame('Shop', view('shop::index')->render());
    }

    public function test_a_generated_command_is_registered_and_runs(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:command', ['name' => 'SyncOrders', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        GeneratedClass::load($this->layerPath('src/Console/Commands/SyncOrders.php'), 'Functional\ProviderLayer\Console\Commands\SyncOrders');
        $provider->register($this->app);
        $this->app->make(Kernel::class)->setArtisan(null);

        $this->assertInstanceOf('Functional\ProviderLayer\Console\Commands\SyncOrders', $this->app->make(Kernel::class)->all()['app:sync-orders']);
        $this->artisan('app:sync-orders')->assertExitCode(0);
    }

    public function test_a_command_generated_twice_is_registered_once(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:command', ['name' => 'PurgeCarts', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:command', ['name' => 'PurgeCarts', '--layer' => LayerFixture::PROVIDER_LAYER, '--force' => true])->assertExitCode(0);

        $this->assertSame(1, substr_count($provider->source(), 'PurgeCarts::class'));
    }

    public function test_a_line_reformatted_in_the_provider_is_not_added_again(): void
    {
        $provider = $this->layerFixture()->providerLayer(<<<'PHP'
                public function boot(): void
                {
                    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
                    $this->commands([
                        \Functional\ProviderLayer\Console\Commands\SyncOrders::class,
                    ]);
                }
            PHP);
        $before = $provider->source();

        $this->artisan('osdd:migration', ['name' => 'create_carts_table', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:command', ['name' => 'SyncOrders', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame($before, $provider->source());
    }

    public function test_a_commented_out_line_does_not_count_as_registered(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod('// $this->commands([\Functional\ProviderLayer\Console\Commands\SyncOrders::class]);'));

        $this->artisan('osdd:command', ['name' => 'SyncOrders', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame(2, substr_count($provider->source(), 'SyncOrders::class'));
    }

    public function test_a_command_in_a_layer_without_provider_prints_the_line(): void
    {
        $this->artisan('osdd:command', ['name' => 'CloseDay', '--layer' => 'functional/test-layer'])
            ->expectsOutputToContain('has no service provider')
            ->expectsOutputToContain('$this->commands([\Functional\TestLayer\Console\Commands\CloseDay::class]);')
            ->assertExitCode(0);
    }

    private function layerPath(string $path): string
    {
        return $this->project()->path(LayerFixture::PROVIDER_LAYER.'/'.$path);
    }
}
