<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Concerns\CreatesProviderLayer;
use Lomkit\Osdd\Tests\TestCase;

class ProviderInjectionTest extends TestCase
{
    use CreatesProviderLayer;

    protected function tearDown(): void
    {
        $this->deleteProviderLayer();
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/config'));

        foreach (glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) ?: [] as $file) {
            $this->app['files']->delete($file);
        }

        parent::tearDown();
    }

    public function test_migrations_load_when_a_helper_sits_between_boot_and_register(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
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

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertContains($this->providerLayerPath('database/migrations'), $this->migrationPathsAfterRegistering($provider));
    }

    public function test_migrations_load_from_a_provider_declared_outside_the_providers_folder(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
                public function register(): void
                {
                    //
                }

                public function boot(): void
                {
                    //
                }
            PHP, directory: 'Support/Boot');

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertStringContainsString(
            "\$this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');",
            (string) file_get_contents($provider['path']),
        );
        $this->assertContains($this->providerLayerPath('database/migrations'), $this->migrationPathsAfterRegistering($provider));
    }

    public function test_config_overrides_are_registered_in_register(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
                public function boot(): void
                {
                    //
                }

                public function register(): void
                {
                    //
                }
            PHP);

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        file_put_contents($this->providerLayerPath('config/payments.php'), "<?php\n\nreturn ['driver' => 'layer'];\n");
        $this->app['config']->set('payments', ['driver' => 'default', 'currency' => 'EUR']);

        require_once $provider['path'];
        (new $provider['class']($this->app))->register();

        $this->assertSame(['driver' => 'layer', 'currency' => 'EUR'], $this->app['config']->get('payments'));
    }

    public function test_migrations_are_registered_in_the_conventional_provider_of_a_dotted_package_name(): void
    {
        $layer = $this->app->basePath('functional/foo.bar');
        $this->app['files']->ensureDirectoryExists($layer.'/src/Providers');
        $this->app['files']->put($layer.'/composer.json', (string) json_encode([
            'name' => 'functional/foo.bar',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\FooBar\\' => 'src/']],
        ]));
        $this->app['files']->put($layer.'/src/Providers/FooBarServiceProvider.php', $this->providerFor('FooBarServiceProvider'));

        try {
            $this->artisan('osdd:migration', ['name' => 'create_bars_table', '--layer' => 'functional/foo.bar'])
                ->doesntExpectOutputToContain('has no service provider')
                ->assertExitCode(0);

            $this->assertStringContainsString(
                "\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');",
                (string) file_get_contents($layer.'/src/Providers/FooBarServiceProvider.php'),
            );
        } finally {
            $this->app['files']->deleteDirectory($layer);
        }
    }

    public function test_it_prints_the_line_to_add_when_the_layer_has_no_provider(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => 'functional/test-layer'])
            ->expectsOutputToContain('has no service provider')
            ->expectsOutputToContain("\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');")
            ->assertExitCode(0);
    }

    public function test_it_prints_the_line_to_add_when_the_method_is_missing(): void
    {
        $this->createProviderLayer(<<<'PHP'
                public function register(): void
                {
                    //
                }
            PHP);

        $this->artisan('osdd:migration', ['name' => 'create_orders_table', '--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('No boot() method found')
            ->expectsOutputToContain("\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');")
            ->assertExitCode(0);
    }

    /**
     * @param  array{class: class-string, path: string}  $provider
     * @return list<string>
     */
    private function migrationPathsAfterRegistering(array $provider): array
    {
        require_once $provider['path'];
        $this->app->register($provider['class']);

        return array_values(array_filter(array_map('realpath', $this->app['migrator']->paths())));
    }

    private function providerFor(string $class): string
    {
        return "<?php\n\nnamespace Functional\\FooBar\\Providers;\n\nuse Lomkit\\Osdd\\LayerServiceProvider;\n\nclass {$class} extends LayerServiceProvider\n{\n    public function boot(): void\n    {\n        //\n    }\n}\n";
    }

    private function providerLayerPath(string $relative): string
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath(self::PROVIDER_LAYER.'/database/migrations'));

        return (string) realpath($this->app->basePath(self::PROVIDER_LAYER)).'/'.$relative;
    }
}
