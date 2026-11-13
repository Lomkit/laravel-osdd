<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\ServiceProvider;
use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\Tests\Fixtures\IntermediateLayerServiceProvider;
use Lomkit\Osdd\Tests\Fixtures\ProviderFile;
use Lomkit\Osdd\Tests\TestCase;

class NonLayerProviderTest extends TestCase
{
    private const string LAYER = 'functional/third-party';

    private const string METHODS = <<<'PHP'
            public function boot(): void
            {
                //
            }

            public function register(): void
            {
                //
            }
        PHP;

    public function test_osdd_config_leaves_a_plain_service_provider_unchanged_and_prints_the_line(): void
    {
        [$provider] = $this->layerWithProviders([ServiceProvider::class]);
        $before = $provider->source();

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => self::LAYER])
            ->expectsOutputToContain('Layer ['.self::LAYER.'] has no service provider extending '.LayerServiceProvider::class.'. Create one with osdd:provider, then add this line to its register() method:')
            ->expectsOutputToContain("\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');")
            ->assertExitCode(0);

        $this->assertSame($before, $provider->source());
        $this->assertFileExists($this->project()->path(self::LAYER.'/config/payments.php'));
    }

    public function test_osdd_migration_leaves_a_loaded_plain_service_provider_unchanged(): void
    {
        [$provider] = $this->layerWithProviders([ServiceProvider::class]);
        require_once $provider->path;
        $before = $provider->source();

        $this->artisan('osdd:migration', ['name' => 'create_payments_table', '--layer' => self::LAYER])
            ->expectsOutputToContain("\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');")
            ->assertExitCode(0);

        $this->assertSame($before, $provider->source());
    }

    public function test_the_first_listed_layer_service_provider_receives_the_call(): void
    {
        [$plain, $layer] = $this->layerWithProviders([ServiceProvider::class, LayerServiceProvider::class]);
        $before = $plain->source();

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => self::LAYER])->assertExitCode(0);

        $this->assertSame($before, $plain->source());
        $this->assertStringContainsString('overrideConfigFrom(', $layer->source());
    }

    public function test_a_loaded_provider_extending_layer_service_provider_through_a_base_class_receives_the_call(): void
    {
        [$provider] = $this->layerWithProviders([IntermediateLayerServiceProvider::class]);
        require_once $provider->path;

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => self::LAYER])->assertExitCode(0);

        $this->assertStringContainsString('overrideConfigFrom(', $provider->source());
    }

    public function test_a_provider_that_cannot_be_loaded_yet_counts_only_when_it_extends_layer_service_provider_directly(): void
    {
        [$provider] = $this->layerWithProviders([IntermediateLayerServiceProvider::class]);
        $before = $provider->source();

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => self::LAYER])
            ->expectsOutputToContain('has no service provider extending')
            ->assertExitCode(0);

        $this->assertSame($before, $provider->source());
    }

    /**
     * @param  list<class-string>  $parents
     * @return list<ProviderFile>
     */
    private function layerWithProviders(array $parents): array
    {
        $layerPath = $this->app->basePath(self::LAYER);
        $providers = array_map(
            fn (string $parent): ProviderFile => $this->layerFixture()->provider($layerPath, 'Functional\\ThirdParty', self::METHODS, parent: $parent),
            $parents,
        );

        $this->layerFixture()->layerAt($layerPath, self::LAYER, [
            'autoload' => ['psr-4' => ['Functional\\ThirdParty\\' => 'src/']],
            'extra' => ['laravel' => ['providers' => array_map(fn (ProviderFile $provider): string => $provider->class, $providers)]],
        ]);

        return $providers;
    }
}
