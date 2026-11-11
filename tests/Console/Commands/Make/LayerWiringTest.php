<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Contracts\Console\Kernel;
use Lomkit\Osdd\Tests\Concerns\CompilesViews;
use Lomkit\Osdd\Tests\Concerns\CreatesProviderLayer;
use Lomkit\Osdd\Tests\TestCase;

class LayerWiringTest extends TestCase
{
    use CompilesViews;
    use CreatesProviderLayer;

    private const string EMPTY_PROVIDER = <<<'PHP'
            public function boot(): void
            {
                //
            }
        PHP;

    protected function tearDown(): void
    {
        $this->deleteProviderLayer();

        parent::tearDown();
    }

    public function test_a_generated_view_resolves_under_the_layer_namespace(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:view', ['name' => 'orders/index', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertStringContainsString(
            "\$this->withViews(__DIR__ . '/../../resources/views');",
            (string) file_get_contents($provider['path']),
        );

        file_put_contents($this->app->basePath(self::PROVIDER_LAYER.'/resources/views/orders/index.blade.php'), 'Orders: {{ $count }}');
        require_once $provider['path'];
        $this->app->register($provider['class']);

        $this->assertSame('Orders: 3', view('provider-layer::orders.index', ['count' => 3])->render());
    }

    public function test_a_second_view_does_not_register_the_views_twice(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:view', ['name' => 'index', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:view', ['name' => 'show', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame(1, substr_count((string) file_get_contents($provider['path']), 'withViews('));
    }

    public function test_an_existing_views_call_with_another_namespace_is_kept(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
                public function boot(): void
                {
                    $this->withViews(__DIR__ . '/../../resources/views', 'shop');
                }
            PHP);

        $this->artisan('osdd:view', ['name' => 'index', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $source = (string) file_get_contents($provider['path']);
        $this->assertSame(1, substr_count($source, 'withViews('));
        $this->assertStringContainsString("'shop'", $source);
    }

    public function test_a_generated_command_is_registered_and_runs(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:command', ['name' => 'SyncOrders', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertStringContainsString(
            '$this->commands([\Functional\ProviderLayer\Console\Commands\SyncOrders::class]);',
            (string) file_get_contents($provider['path']),
        );

        require_once $this->app->basePath(self::PROVIDER_LAYER.'/src/Console/Commands/SyncOrders.php');
        require_once $provider['path'];
        $this->app->register($provider['class']);
        $this->app->make(Kernel::class)->setArtisan(null);

        $this->assertInstanceOf('Functional\ProviderLayer\Console\Commands\SyncOrders', $this->app->make(Kernel::class)->all()['app:sync-orders']);
        $this->artisan('app:sync-orders')->assertExitCode(0);
    }

    public function test_a_command_generated_twice_is_registered_once(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:command', ['name' => 'PurgeCarts', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:command', ['name' => 'PurgeCarts', '--layer' => self::PROVIDER_LAYER, '--force' => true])->assertExitCode(0);

        $this->assertSame(1, substr_count((string) file_get_contents($provider['path']), 'PurgeCarts::class'));
    }

    public function test_a_line_reformatted_in_the_provider_is_not_added_again(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
                public function boot(): void
                {
                    $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
                    $this->commands([
                        \Functional\ProviderLayer\Console\Commands\SyncOrders::class,
                    ]);
                }
            PHP);
        $before = (string) file_get_contents($provider['path']);

        $this->artisan('osdd:migration', ['name' => 'create_carts_table', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:command', ['name' => 'SyncOrders', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame($before, (string) file_get_contents($provider['path']));
    }

    public function test_a_commented_out_line_does_not_count_as_registered(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
                public function boot(): void
                {
                    // $this->commands([\Functional\ProviderLayer\Console\Commands\SyncOrders::class]);
                }
            PHP);

        $this->artisan('osdd:command', ['name' => 'SyncOrders', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame(2, substr_count((string) file_get_contents($provider['path']), 'SyncOrders::class'));
    }

    public function test_a_command_in_a_layer_without_provider_prints_the_line(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        $this->artisan('osdd:command', ['name' => 'CloseDay', '--layer' => 'functional/test-layer'])
            ->expectsOutputToContain('has no service provider')
            ->expectsOutputToContain('$this->commands([\Functional\TestLayer\Console\Commands\CloseDay::class]);')
            ->assertExitCode(0);

        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Console'));
    }
}
