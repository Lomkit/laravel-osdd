<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\Fixtures\ProviderFile;
use Lomkit\Osdd\Tests\TestCase;

class ProviderInjectionTest extends TestCase
{
    public function test_migrations_load_when_a_helper_sits_between_boot_and_register(): void
    {
        $provider = $this->layerFixture()->providerLayer(<<<'PHP'
                public function boot(): void
                {
                    //
                }

                protected function configureRateLimiting(): void
                {
                    //
                }

                public function register(): void
                {
                    //
                }
            PHP);

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertContains($this->layerMigrationsPath(), $this->migrationPathsAfterRegistering($provider));
    }

    public function test_migrations_load_from_a_provider_declared_outside_the_providers_folder(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod(), folder: 'Support/Boot');

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertContains($this->layerMigrationsPath(), $this->migrationPathsAfterRegistering($provider));
    }

    public function test_migrations_are_registered_in_the_conventional_provider_of_a_dotted_package_name(): void
    {
        $layer = $this->layerFixture()->layer('functional/foo.bar', ['autoload' => ['psr-4' => ['Functional\\FooBar\\' => 'src/']]]);
        $this->app['files']->ensureDirectoryExists($layer.'/src/Providers');
        $this->app['files']->put($layer.'/src/Providers/FooBarServiceProvider.php', "<?php\n\nnamespace Functional\\FooBar\\Providers;\n\nuse Lomkit\\Osdd\\LayerServiceProvider;\n\nclass FooBarServiceProvider extends LayerServiceProvider\n{\n".LayerFixture::bootMethod()."\n}\n");

        $this->artisan('osdd:migration', ['name' => 'create_bars_table', '--layer' => 'functional/foo.bar'])
            ->doesntExpectOutputToContain('has no service provider')
            ->assertExitCode(0);

        $this->assertStringContainsString('loadMigrationsFrom(', (string) file_get_contents($layer.'/src/Providers/FooBarServiceProvider.php'));
    }

    public function test_it_prints_the_line_to_add_when_the_method_is_missing(): void
    {
        $this->layerFixture()->providerLayer(<<<'PHP'
                public function register(): void
                {
                    //
                }
            PHP);

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('No boot() method found')
            ->expectsOutputToContain("\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');")
            ->assertExitCode(0);
    }

    /**
     * @return list<string>
     */
    private function migrationPathsAfterRegistering(ProviderFile $provider): array
    {
        $provider->register($this->app);

        return array_values(array_filter(array_map('realpath', $this->app['migrator']->paths())));
    }

    private function layerMigrationsPath(): string
    {
        return $this->project()->path(LayerFixture::PROVIDER_LAYER.'/database/migrations');
    }
}
