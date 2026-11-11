<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class LayerCommandTest extends TestCase
{
    private const array ALL_GENERATORS = ['migration', 'model', 'factory', 'seeder', 'provider', 'test', 'controller', 'policy', 'routes'];

    private const array DEFAULT_GENERATORS = ['migration', 'model', 'factory', 'provider', 'test'];

    private string $composerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composerPath = $this->app->basePath('composer.json');
        $this->app['files']->put($this->composerPath, json_encode(['require' => new \stdClass], JSON_PRETTY_PRINT).PHP_EOL);
    }

    public function test_it_creates_layer_with_all_generators(): void
    {
        $this->createInteractively('my-layer', self::ALL_GENERATORS);

        foreach ([
            'database/migrations', 'src/Models', 'database/factories', 'database/seeders', 'src/Providers',
            'tests/Feature', 'src/Http/Controllers', 'src/Policies',
            'routes/web.php', 'routes/api.php', 'routes/console.php', 'routes/channels.php',
        ] as $path) {
            $this->assertFileExists($this->layerPath('my-layer', $path));
        }
    }

    public function test_it_creates_layer_with_selected_generators(): void
    {
        $this->createInteractively('my-layer', ['migration', 'model']);

        $this->assertFileExists($this->layerPath('my-layer', 'database/migrations'));
        $this->assertFileExists($this->layerPath('my-layer', 'src/Models'));

        foreach (['database/factories', 'database/seeders', 'src/Providers', 'tests/Feature', 'src/Http/Controllers', 'src/Policies', 'routes'] as $path) {
            $this->assertFileDoesNotExist($this->layerPath('my-layer', $path));
        }
    }

    public function test_it_creates_layer_with_no_generators(): void
    {
        $this->createInteractively('my-layer', []);

        $this->assertSame(['composer.json'], array_values(array_diff(scandir($this->layerPath('my-layer', '')) ?: [], ['.', '..'])));
    }

    public function test_the_manifest_declares_the_layer_its_namespaces_and_its_provider(): void
    {
        $this->createInteractively('my-layer', self::ALL_GENERATORS);

        $manifest = json_decode((string) file_get_contents($this->layerPath('my-layer', 'composer.json')), true);

        $this->assertSame('functional/my-layer', $manifest['name']);
        $this->assertSame('layer', $manifest['type']);
        $this->assertSame('1.0.0', $manifest['version']);
        $this->assertSame(['lomkit/laravel-osdd' => '*'], $manifest['require']);
        $this->assertSame([
            'Functional\\MyLayer\\' => 'src/',
            'Functional\\MyLayer\\Database\\Seeders\\' => 'database/seeders/',
            'Functional\\MyLayer\\Database\\Factories\\' => 'database/factories/',
        ], $manifest['autoload']['psr-4']);
        $this->assertSame(['Functional\\MyLayer\\Providers\\MyLayerServiceProvider'], $this->app->make(Layers::class)->find('functional/my-layer')->providers);
    }

    public function test_the_provider_boots_the_layer_resources(): void
    {
        $this->createInteractively('booted-layer', self::ALL_GENERATORS);
        $this->app['files']->append($this->layerPath('booted-layer', 'routes/web.php'), "\nRoute::get('/booted', fn () => 'web');\n");
        $this->app['files']->append($this->layerPath('booted-layer', 'routes/api.php'), "\nRoute::get('/booted', fn () => 'api');\n");
        $this->app['files']->ensureDirectoryExists($this->layerPath('booted-layer', 'resources/views'));
        $this->app['files']->ensureDirectoryExists($this->layerPath('booted-layer', 'lang'));

        $provider = GeneratedClass::load(
            $this->layerPath('booted-layer', 'src/Providers/BootedLayerServiceProvider.php'),
            'Functional\BootedLayer\Providers\BootedLayerServiceProvider',
        );
        $this->app->register($provider->getName());

        $this->assertTrue($provider->isSubclassOf(LayerServiceProvider::class));
        $this->assertContains($this->layerPath('booted-layer', 'database/migrations'), array_map('realpath', $this->app['migrator']->paths()));
        $this->assertSame(['Functional\BootedLayer\Database\Seeders\BootedLayerSeeder'], $this->app->make(SeederRegistry::class)->seeders());
        $this->assertArrayHasKey('booted-layer', $this->app['view']->getFinder()->getHints());
        $this->assertArrayHasKey('booted-layer', $this->app['translator']->getLoader()->namespaces());
        $this->assertSame(['web'], $this->app['router']->getRoutes()->match(Request::create('/booted'))->middleware());
        $this->assertSame(['api'], $this->app['router']->getRoutes()->match(Request::create('/api/booted'))->middleware());
    }

    public function test_it_generates_named_seeder_for_layer(): void
    {
        $this->createInteractively('seeded-layer', ['seeder']);

        GeneratedClass::load($this->layerPath('seeded-layer', 'database/seeders/SeededLayerSeeder.php'), 'Functional\SeededLayer\Database\Seeders\SeededLayerSeeder');
    }

    public function test_a_layer_sharing_a_package_name_gets_its_own_resource_namespace(): void
    {
        $this->layerFixture()->bucket('technical');

        foreach (['functional/orders', 'technical/orders'] as $name) {
            $this->artisan('osdd:layer', ['name' => $name, '--generators' => ['provider'], '--no-composer-update' => true])->assertExitCode(0);
            $this->app['files']->ensureDirectoryExists($this->project()->path($name.'/resources/views'));
        }

        foreach (['Functional', 'Technical'] as $vendor) {
            $this->app->register(GeneratedClass::load(
                $this->project()->path(strtolower($vendor).'/orders/src/Providers/OrdersServiceProvider.php'),
                "{$vendor}\\Orders\\Providers\\OrdersServiceProvider",
            )->getName());
        }

        $hints = $this->app['view']->getFinder()->getHints();
        $this->assertSame([$this->project()->path('functional/orders/resources/views')], array_map('realpath', $hints['orders']));
        $this->assertSame([$this->project()->path('technical/orders/resources/views')], array_map('realpath', $hints['technical-orders']));
    }

    public function test_it_does_not_declare_a_provider_when_not_selected(): void
    {
        $this->createInteractively('my-layer', ['migration', 'model']);

        $this->assertSame([], $this->app->make(Layers::class)->find('functional/my-layer')->providers);
    }

    public function test_it_skips_path_prompt_when_only_one_path_is_configured(): void
    {
        $this->createInteractively('my-layer', self::DEFAULT_GENERATORS);

        $this->assertFileExists($this->layerPath('my-layer', 'composer.json'));
    }

    public function test_it_asks_for_path_when_multiple_paths_are_configured(): void
    {
        $this->layerFixture()->bucket('technical');

        $this->artisan('osdd:layer')
            ->expectsQuestion('Where should the layer be created?', 'functional')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', self::DEFAULT_GENERATORS, self::ALL_GENERATORS)
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileExists($this->layerPath('my-layer', 'composer.json'));
        $this->assertFileDoesNotExist($this->project()->path('technical/my-layer/composer.json'));
    }

    public function test_it_validates_layer_name(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'InvalidName')
            ->assertFailed();
    }

    public function test_migration_table_name_replaces_hyphens_with_underscores(): void
    {
        $this->createInteractively('external-accounts', ['migration']);

        $this->artisan('migrate', ['--path' => $this->layerPath('external-accounts', 'database/migrations'), '--realpath' => true])->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('external_accounts'));
    }

    public function test_namespace_is_correctly_derived_from_hyphenated_names(): void
    {
        $this->createInteractively('my-auth-layer', ['provider']);

        $layer = $this->app->make(Layers::class)->find('functional/my-auth-layer');
        $provider = GeneratedClass::load(
            $this->layerPath('my-auth-layer', 'src/Providers/MyAuthLayerServiceProvider.php'),
            'Functional\MyAuthLayer\Providers\MyAuthLayerServiceProvider',
        );

        $this->assertSame('Functional\\MyAuthLayer\\', $layer->rootNamespace);
        $this->assertTrue($provider->isSubclassOf(LayerServiceProvider::class));
    }

    public function test_it_registers_layer_as_path_repository_in_composer_json(): void
    {
        $this->createInteractively('my-layer', self::DEFAULT_GENERATORS);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $this->assertSame([['type' => 'path', 'url' => './functional/*']], $composer['repositories']);
        $this->assertSame(['functional/my-layer' => '*'], $composer['require']);
    }

    public function test_it_does_not_duplicate_repository_on_repeat_runs(): void
    {
        $this->createInteractively('my-layer', self::DEFAULT_GENERATORS);
        $this->createInteractively('my-auth-layer', self::DEFAULT_GENERATORS);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $this->assertSame([['type' => 'path', 'url' => './functional/*']], $composer['repositories']);
    }

    public function test_it_keeps_empty_objects_in_the_root_composer_json(): void
    {
        $this->app['files']->put($this->composerPath, <<<'JSON'
            {
                "require": {},
                "require-dev": {},
                "config": {"allow-plugins": {}, "sort-packages": true}
            }
            JSON);

        $this->artisan('osdd:layer', ['name' => 'functional/my-layer', '--generators' => ['provider']])
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), false);
        $this->assertEquals(new \stdClass, $composer->{'require-dev'});
        $this->assertEquals(new \stdClass, $composer->config->{'allow-plugins'});
        $this->assertSame('*', $composer->require->{'functional/my-layer'});
    }

    public function test_it_registers_a_layer_bucket_outside_the_project_with_a_relative_url(): void
    {
        $outside = $this->project()->path('outside');
        $this->app->setBasePath($this->project()->path('app-root'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath());
        $this->app['files']->move($this->composerPath, $this->app->basePath('composer.json'));
        $this->app['config']->set('osdd.layers.paths', ['functional' => $outside]);

        $this->artisan('osdd:layer', ['name' => 'functional/my-layer', '--target-path' => $outside, '--generators' => ['provider']])
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->app->basePath('composer.json')), true);
        $this->assertSame([['type' => 'path', 'url' => '../outside/*']], $composer['repositories']);
    }

    public function test_it_skips_composer_registration_when_no_composer_json_exists(): void
    {
        $this->app['files']->delete($this->composerPath);

        $this->createInteractively('my-layer', self::DEFAULT_GENERATORS);

        $this->assertFileExists($this->layerPath('my-layer', 'composer.json'));
        $this->assertFileDoesNotExist($this->composerPath);
    }

    public function test_it_links_policy_to_model_when_both_are_selected(): void
    {
        $this->createInteractively('fleet', ['model', 'policy']);

        $policy = GeneratedClass::load($this->layerPath('fleet', 'src/Policies/FleetPolicy.php'), 'Functional\Fleet\Policies\FleetPolicy');
        $type = $policy->getMethod('view')->getParameters()[1]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('Functional\Fleet\Models\Fleet', $type->getName());
    }

    public function test_it_generates_plain_policy_when_model_not_selected(): void
    {
        $this->createInteractively('audit', ['policy']);

        $policy = GeneratedClass::load($this->layerPath('audit', 'src/Policies/AuditPolicy.php'), 'Functional\Audit\Policies\AuditPolicy');

        $this->assertSame(['__construct'], array_map(fn (\ReflectionMethod $method): string => $method->getName(), $policy->getMethods()));
    }

    /**
     * @param  list<string>  $generators
     */
    private function createInteractively(string $package, array $generators): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', $package)
            ->expectsChoice('Which generators should be run?', $generators, self::ALL_GENERATORS)
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);
    }

    private function layerPath(string $package, string $path): string
    {
        return $this->project()->path(rtrim("functional/{$package}/{$path}", '/'));
    }
}
