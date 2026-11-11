<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

class LayerCommandOptionsTest extends TestCase
{
    private const array CREATED_LAYERS = [
        'functional/orders', 'functional/billing', 'functional/plain-layer', 'functional/seeded-layer',
        'functional/process-layer', 'technical/orders', 'technical/logging',
    ];

    private string $composerPath;

    private ?string $composerOriginal = null;

    private string $fakeBin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));
        $this->composerPath = $this->app->basePath('composer.json');

        if ($this->app['files']->exists($this->composerPath)) {
            $this->composerOriginal = $this->app['files']->get($this->composerPath);
        }

        $this->app['files']->put($this->composerPath, json_encode(['require' => new \stdClass], JSON_PRETTY_PRINT).PHP_EOL);

        $this->fakeBin = sys_get_temp_dir().'/osdd-fake-bin-'.uniqid();
        mkdir($this->fakeBin);
        file_put_contents($this->fakeBin.'/composer', "#!/bin/sh\necho \"\$@\" >> \"{$this->fakeBin}/calls\"\n");
        chmod($this->fakeBin.'/composer', 0755);
    }

    protected function tearDown(): void
    {
        foreach (self::CREATED_LAYERS as $layer) {
            $this->app['files']->deleteDirectory($this->app->basePath($layer));
        }

        $this->app['files']->deleteDirectory($this->app->basePath('technical'));
        $this->app['files']->deleteDirectory($this->fakeBin);

        if ($this->composerOriginal !== null) {
            $this->app['files']->put($this->composerPath, $this->composerOriginal);
        } else {
            $this->app['files']->delete($this->composerPath);
        }

        parent::tearDown();
    }

    public function test_it_takes_the_bucket_from_the_vendor_when_not_interactive(): void
    {
        $this->artisan('osdd:layer', ['name' => 'technical/logging', '--generators' => ['service-provider'], '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('technical/logging/composer.json'));
        $this->assertFileDoesNotExist($this->app->basePath('functional/logging/composer.json'));
    }

    public function test_it_takes_the_bucket_from_the_vendor_without_asking(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-composer-update' => true])
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('functional/orders/composer.json'));
    }

    public function test_it_fails_when_the_vendor_matches_no_bucket_and_not_interactive(): void
    {
        $this->artisan('osdd:layer', ['name' => 'acme/orders', '--no-interaction' => true])
            ->expectsOutputToContain('The vendor [acme] matches no key of osdd.layers.paths (functional, technical)')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/orders'));
    }

    public function test_it_fails_without_a_name_when_not_interactive(): void
    {
        $this->artisan('osdd:layer', ['--no-interaction' => true])
            ->expectsOutputToContain('Pass the layer name (vendor/package)')
            ->assertExitCode(1);
    }

    public function test_it_runs_the_default_generators_when_not_interactive(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--no-interaction' => true])
            ->assertExitCode(0);

        $layer = $this->app->basePath('functional/orders');
        $this->assertFileExists($layer.'/src/Models/Order.php');
        $this->assertFileExists($layer.'/database/factories/OrderFactory.php');
        $this->assertFileExists($layer.'/src/Providers/OrdersServiceProvider.php');
        $this->assertFileExists($layer.'/tests/Feature/OrdersTest.php');
        $this->assertNotEmpty(glob($layer.'/database/migrations/*_create_orders_table.php'));
        $this->assertDirectoryDoesNotExist($layer.'/database/seeders');
    }

    public function test_it_accepts_comma_separated_generators(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['model,policy', 'routes'], '--no-interaction' => true])
            ->assertExitCode(0);

        $layer = $this->app->basePath('functional/orders');
        $this->assertFileExists($layer.'/src/Models/Order.php');
        $this->assertFileExists($layer.'/src/Policies/OrdersPolicy.php');
        $this->assertFileExists($layer.'/routes/web.php');
        $this->assertDirectoryDoesNotExist($layer.'/src/Providers');
    }

    public function test_it_rejects_an_unknown_generator_and_lists_the_valid_ones(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['model,modle'], '--no-interaction' => true])
            ->expectsOutputToContain('Unknown generator [modle]. Valid generators: migration, model, factory, seeder, service-provider, test, controller, policy, routes.')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/orders'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidNames(): array
    {
        return [
            'no vendor' => ['orders', 'Use a Composer package name'],
            'uppercase' => ['functional/My_Orders', 'Use a Composer package name'],
            'space' => ['functional/my orders', 'Use a Composer package name'],
            'leading digit' => ['functional/2fa', 'is not a valid PHP namespace'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_it_rejects_a_name_that_is_not_a_composer_package_name(string $name, string $message): void
    {
        $before = $this->app['files']->get($this->composerPath);

        $this->artisan('osdd:layer', ['name' => $name, '--generators' => ['service-provider'], '--no-interaction' => true])
            ->expectsOutputToContain($message)
            ->assertExitCode(1);

        $this->assertSame($before, $this->app['files']->get($this->composerPath));
        $this->assertSame([], glob($this->app->basePath('functional/*'), GLOB_ONLYDIR) === false ? [] : array_values(array_filter(
            glob($this->app->basePath('functional/*'), GLOB_ONLYDIR),
            fn (string $directory): bool => basename($directory) !== 'test-layer',
        )));
    }

    public function test_dots_and_underscores_become_namespace_words(): void
    {
        $this->assertSame('Functional\\SalesOrders', LayerName::fromString('functional/sales.orders')->namespace());
        $this->assertSame('Functional\\SalesOrders', LayerName::fromString('functional/sales_orders')->namespace());
    }

    public function test_it_refuses_an_existing_layer(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-interaction' => true])
            ->assertExitCode(0);

        $provider = $this->app->basePath('functional/orders/src/Providers/OrdersServiceProvider.php');
        file_put_contents($provider, "<?php // edited by hand\n");

        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['routes'], '--no-interaction' => true])
            ->expectsOutputToContain('already exists. Pass --force')
            ->assertExitCode(1);

        $this->assertSame("<?php // edited by hand\n", file_get_contents($provider));
        $this->assertFileDoesNotExist($this->app->basePath('functional/orders/routes/web.php'));
        $this->assertStringContainsString('OrdersServiceProvider', (string) file_get_contents($this->app->basePath('functional/orders/composer.json')));
    }

    public function test_force_overwrites_an_existing_layer(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider', 'model'], '--no-interaction' => true])
            ->assertExitCode(0);

        $provider = $this->app->basePath('functional/orders/src/Providers/OrdersServiceProvider.php');
        file_put_contents($provider, "<?php // edited by hand\n");

        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider', 'model'], '--force' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertStringContainsString('class OrdersServiceProvider extends LayerServiceProvider', (string) file_get_contents($provider));
    }

    public function test_the_default_provider_does_not_reference_a_seeder(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/plain-layer', '--no-interaction' => true])->assertExitCode(0);

        $provider = $this->app->basePath('functional/plain-layer/src/Providers/PlainLayerServiceProvider.php');
        $this->assertStringNotContainsString('Seeder', (string) file_get_contents($provider));

        require_once $provider;
        $this->app->register('Functional\\PlainLayer\\Providers\\PlainLayerServiceProvider');

        $this->assertSame([], $this->app->make(SeederRegistry::class)->seeders());
    }

    public function test_a_seeder_added_later_is_registered_once(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/seeded-layer', '--generators' => ['seeder', 'service-provider'], '--no-interaction' => true])->assertExitCode(0);
        $this->artisan('osdd:seeder', ['name' => 'SeededLayerSeeder', '--layer' => 'functional/seeded-layer'])->assertExitCode(0);

        $provider = $this->app->basePath('functional/seeded-layer/src/Providers/SeededLayerServiceProvider.php');
        $this->assertSame(1, substr_count((string) file_get_contents($provider), 'loadSeeders('));

        require_once $provider;
        $this->app->register('Functional\\SeededLayer\\Providers\\SeededLayerServiceProvider');

        $this->assertSame(['Functional\\SeededLayer\\Database\\Seeders\\SeededLayerSeeder'], $this->app->make(SeederRegistry::class)->seeders());
    }

    public function test_it_does_not_run_composer_update_when_not_interactive(): void
    {
        $this->withFakeComposer(fn () => $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-interaction' => true])
            ->expectsOutputToContain('composer update functional/orders')
            ->assertExitCode(0));

        $this->assertFileDoesNotExist($this->fakeBin.'/calls');
    }

    public function test_composer_update_option_runs_it_without_asking(): void
    {
        $this->withFakeComposer(fn () => $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--composer-update' => true, '--no-interaction' => true])
            ->assertExitCode(0));

        $this->assertSame("update functional/orders\n", file_get_contents($this->fakeBin.'/calls'));
    }

    public function test_no_composer_update_option_skips_the_question(): void
    {
        $this->withFakeComposer(fn () => $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-composer-update' => true])
            ->assertExitCode(0));

        $this->assertFileDoesNotExist($this->fakeBin.'/calls');
    }

    public function test_without_a_terminal_it_creates_the_layer_and_skips_composer_update(): void
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/testbench', 'osdd:layer', 'functional/process-layer', '--generators=service-provider,test'],
            dirname(__DIR__, 3),
            ['PATH' => $this->fakeBin.PATH_SEPARATOR.getenv('PATH')],
        );
        $process->setInput('');
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        $this->assertFileExists($this->app->basePath('functional/process-layer/src/Providers/ProcessLayerServiceProvider.php'));
        $this->assertFileExists($this->app->basePath('functional/process-layer/tests/Feature/ProcessLayerTest.php'));
        $this->assertFileDoesNotExist($this->fakeBin.'/calls');
    }

    public function test_force_keeps_the_existing_layer_composer_json(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-interaction' => true])
            ->assertExitCode(0);

        $layerComposerPath = $this->app->basePath('functional/orders/composer.json');
        $layerComposer = json_decode((string) file_get_contents($layerComposerPath), true);
        $layerComposer['description'] = 'Orders domain';
        $layerComposer['require']['functional/users'] = '*';
        unset($layerComposer['minimum-stability']);
        file_put_contents($layerComposerPath, json_encode($layerComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['routes'], '--force' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $kept = json_decode((string) file_get_contents($layerComposerPath), true);
        $this->assertSame('Orders domain', $kept['description']);
        $this->assertSame(['lomkit/laravel-osdd' => '*', 'functional/users' => '*'], $kept['require']);
        $this->assertSame(['Functional\\Orders\\Providers\\OrdersServiceProvider'], $kept['extra']['laravel']['providers']);
        $this->assertSame('stable', $kept['minimum-stability']);
        $this->assertFileExists($this->app->basePath('functional/orders/routes/web.php'));
    }

    public function test_a_failed_composer_update_fails_the_command(): void
    {
        file_put_contents($this->fakeBin.'/composer', "#!/bin/sh\necho \"\$@\" >> \"{$this->fakeBin}/calls\"\nexit 1\n");

        $this->withFakeComposer(fn () => $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--composer-update' => true, '--no-interaction' => true])
            ->expectsOutputToContain('composer update failed')
            ->assertExitCode(1));

        $this->assertSame("update functional/orders\n", file_get_contents($this->fakeBin.'/calls'));
        $this->assertFileExists($this->app->basePath('functional/orders/src/Providers/OrdersServiceProvider.php'));
    }

    public function test_requires_writes_the_resolved_layers_into_the_new_layer(): void
    {
        $this->artisan('osdd:layer', ['name' => 'technical/logging', '--generators' => ['service-provider'], '--no-interaction' => true])->assertExitCode(0);
        $this->artisan('osdd:layer', ['name' => 'functional/billing', '--generators' => ['service-provider'], '--no-interaction' => true])->assertExitCode(0);

        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--requires' => ['billing,technical/logging'], '--no-interaction' => true])
            ->assertExitCode(0);

        $manifest = json_decode((string) file_get_contents($this->app->basePath('functional/orders/composer.json')), true);
        $this->assertSame(['lomkit/laravel-osdd' => '*', 'functional/billing' => '*', 'technical/logging' => '*'], $manifest['require']);
        $this->assertSame(['Functional\\Orders\\Providers\\OrdersServiceProvider'], $manifest['extra']['laravel']['providers']);

        Artisan::call('osdd:graph');
        $this->assertStringContainsString('functional_orders --> functional_billing'.PHP_EOL.'    functional_orders --> technical_logging', Artisan::output());
    }

    public function test_requires_refuses_an_unknown_layer_before_writing_anything(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--requires' => ['functional/shipping'], '--no-interaction' => true])
            ->expectsOutputToContain('Layer [functional/shipping] not found.')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/orders'));
    }

    public function test_requires_refuses_the_layer_itself(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-interaction' => true])->assertExitCode(0);

        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--requires' => ['orders'], '--force' => true, '--no-interaction' => true])
            ->expectsOutputToContain('Layer [functional/orders] cannot require itself.')
            ->assertExitCode(1);
    }

    public function test_requires_adds_to_the_requirements_of_an_existing_layer_on_force(): void
    {
        $this->artisan('osdd:layer', ['name' => 'functional/billing', '--generators' => ['service-provider'], '--no-interaction' => true])->assertExitCode(0);
        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['service-provider'], '--no-interaction' => true])->assertExitCode(0);

        $this->artisan('osdd:layer', ['name' => 'functional/orders', '--generators' => ['routes'], '--requires' => ['functional/billing'], '--force' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $manifest = json_decode((string) file_get_contents($this->app->basePath('functional/orders/composer.json')), true);
        $this->assertSame(['lomkit/laravel-osdd' => '*', 'functional/billing' => '*'], $manifest['require']);
        $this->assertSame(['Functional\\Orders\\Providers\\OrdersServiceProvider'], $manifest['extra']['laravel']['providers']);
    }

    private function withFakeComposer(\Closure $callback): void
    {
        $path = (string) getenv('PATH');
        putenv('PATH='.$this->fakeBin.PATH_SEPARATOR.$path);

        try {
            $callback();
        } finally {
            putenv('PATH='.$path);
        }
    }
}
