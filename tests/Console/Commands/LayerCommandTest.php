<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class LayerCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    private string $composerPath;

    private ?string $composerOriginal = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composerPath = $this->app->basePath('composer.json');

        if ($this->app['files']->exists($this->composerPath)) {
            $this->composerOriginal = $this->app['files']->get($this->composerPath);
        } else {
            $this->app['files']->put($this->composerPath, json_encode(['require' => new \stdClass], JSON_PRETTY_PRINT).PHP_EOL);
        }
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/my-layer'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/my-auth-layer'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/external-accounts'));
        $this->app['files']->deleteDirectory($this->app->basePath('technical/my-layer'));

        if ($this->composerOriginal !== null) {
            $this->app['files']->put($this->composerPath, $this->composerOriginal);
        } else {
            $this->app['files']->delete($this->composerPath);
        }

        parent::tearDown();
    }

    public function test_it_creates_layer_with_all_generators(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/migrations');
        $this->assertFilenameExists('functional/my-layer/src/Models');
        $this->assertFilenameExists('functional/my-layer/database/factories');
        $this->assertFilenameExists('functional/my-layer/database/seeders');
        $this->assertFilenameExists('functional/my-layer/src/Providers');
        $this->assertFilenameExists('functional/my-layer/tests/Feature');
        $this->assertFilenameExists('functional/my-layer/src/Http/Controllers');
        $this->assertFilenameExists('functional/my-layer/src/Policies');
        $this->assertFilenameExists('functional/my-layer/routes/web.php');
        $this->assertFilenameExists('functional/my-layer/routes/api.php');
        $this->assertFilenameExists('functional/my-layer/routes/console.php');
        $this->assertFilenameExists('functional/my-layer/routes/channels.php');
    }

    public function test_it_does_not_create_routes_directory_when_routes_generator_not_selected(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameNotExists('functional/my-layer/routes');
    }

    public function test_it_generates_named_seeder_for_layer(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/seeders/MyLayerSeeder.php');

        $this->assertFileContains([
            'namespace Functional\MyLayer\Database\Seeders;',
            'class MyLayerSeeder extends Seeder',
        ], 'functional/my-layer/database/seeders/MyLayerSeeder.php');
    }

    public function test_it_creates_correct_composer_json(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            '"name": "functional/my-layer"',
            '"type": "layer"',
            '"version": "1.0.0"',
            '"Functional\\\\MyLayer\\\\": "src/"',
            '"Functional\\\\MyLayer\\\\Database\\\\Seeders\\\\": "database/seeders/"',
            '"Functional\\\\MyLayer\\\\Database\\\\Factories\\\\": "database/factories/"',
            '"lomkit/laravel-osdd": "*"',
            'Functional\\\\MyLayer\\\\Providers\\\\MyLayerServiceProvider',
        ], 'functional/my-layer/composer.json');
    }

    public function test_it_creates_service_provider_with_correct_content(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\MyLayer\Providers;',
            'use Lomkit\Osdd\LayerServiceProvider;',
            'class MyLayerServiceProvider extends LayerServiceProvider',
            "loadMigrationsFrom(__DIR__ . '/../../database/migrations')",
            'loadSeeders([MyLayerSeeder::class])',
            '$this->withRouting(',
            "web: __DIR__ . '/../../routes/web.php'",
            "api: __DIR__ . '/../../routes/api.php'",
            "commands: __DIR__ . '/../../routes/console.php'",
            "channels: __DIR__ . '/../../routes/channels.php'",
        ], 'functional/my-layer/src/Providers/MyLayerServiceProvider.php');

        require_once $this->app->basePath('functional/my-layer/src/Providers/MyLayerServiceProvider.php');

        $this->assertTrue(is_subclass_of('Functional\MyLayer\Providers\MyLayerServiceProvider', LayerServiceProvider::class));
    }

    public function test_it_creates_layer_with_selected_generators(): void
    {
        $selected = ['migration', 'model'];

        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $selected, $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/migrations');
        $this->assertFilenameExists('functional/my-layer/src/Models');

        $this->assertFilenameNotExists('functional/my-layer/database/factories');
        $this->assertFilenameNotExists('functional/my-layer/database/seeders');
        $this->assertFilenameNotExists('functional/my-layer/src/Providers');
        $this->assertFilenameNotExists('functional/my-layer/tests/Feature');
        $this->assertFilenameNotExists('functional/my-layer/src/Http/Controllers');
        $this->assertFilenameNotExists('functional/my-layer/src/Policies');
    }

    public function test_it_creates_layer_with_no_generators(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', [], $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');

        $this->assertFilenameNotExists('functional/my-layer/database/migrations');
        $this->assertFilenameNotExists('functional/my-layer/src/Models');
        $this->assertFilenameNotExists('functional/my-layer/database/factories');
        $this->assertFilenameNotExists('functional/my-layer/database/seeders');
        $this->assertFilenameNotExists('functional/my-layer/src/Providers');
        $this->assertFilenameNotExists('functional/my-layer/tests/Feature');
        $this->assertFilenameNotExists('functional/my-layer/src/Http/Controllers');
        $this->assertFilenameNotExists('functional/my-layer/src/Policies');
    }

    public function test_it_does_not_register_service_provider_in_layer_composer_json_when_not_selected(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', ['migration', 'model'], $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->app->basePath('functional/my-layer/composer.json')), true);

        $this->assertEmpty($composer['extra']['laravel']['providers'] ?? []);
    }

    public function test_it_skips_path_prompt_when_only_one_path_is_configured(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
    }

    public function test_it_asks_for_path_when_multiple_paths_are_configured(): void
    {
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));

        $this->artisan('osdd:layer')
            ->expectsQuestion('Where should the layer be created?', 'functional')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
        $this->assertFilenameNotExists('technical/my-layer/composer.json');
    }

    public function test_it_validates_layer_name(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'InvalidName')
            ->assertFailed();
    }

    public function test_migration_table_name_replaces_hyphens_with_underscores(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'external-accounts')
            ->expectsChoice('Which generators should be run?', ['migration'], $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/external-accounts/database/migrations/*_create_external_accounts_table.php'));
        $this->assertNotEmpty($files);

        $contents = $this->app['files']->get($files[0]);
        $this->assertStringContainsString("Schema::create('external_accounts'", $contents);
    }

    public function test_namespace_is_correctly_derived_from_hyphenated_names(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-auth-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            '"Functional\\\\MyAuthLayer\\\\": "src/"',
            '"Functional\\\\MyAuthLayer\\\\Database\\\\Seeders\\\\": "database/seeders/"',
        ], 'functional/my-auth-layer/composer.json');

        $this->assertFileContains([
            'namespace Functional\MyAuthLayer\Providers;',
            'use Lomkit\Osdd\LayerServiceProvider;',
            'class MyAuthLayerServiceProvider extends LayerServiceProvider',
        ], 'functional/my-auth-layer/src/Providers/MyAuthLayerServiceProvider.php');
    }

    public function test_it_registers_layer_as_path_repository_in_composer_json(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $urls = array_column($composer['repositories'] ?? [], 'url');
        $this->assertContains('./functional/*', $urls);

        $types = array_column($composer['repositories'], 'type');
        $this->assertContains('path', $types);
    }

    public function test_it_adds_layer_to_require_in_composer_json(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $this->assertArrayHasKey('functional/my-layer', $composer['require']);
        $this->assertSame('*', $composer['require']['functional/my-layer']);
    }

    public function test_it_does_not_duplicate_repository_on_repeat_runs(): void
    {
        $run = fn () => $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $run();
        $run();

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $matching = array_filter($composer['repositories'] ?? [], fn ($r) => ($r['url'] ?? '') === './functional/*');
        $this->assertCount(1, $matching);
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

        $this->artisan('osdd:layer', ['name' => 'functional/my-layer', '--generators' => ['service-provider']])
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), false);

        $this->assertEquals(new \stdClass, $composer->{'require-dev'});
        $this->assertEquals(new \stdClass, $composer->config->{'allow-plugins'});
        $this->assertSame('*', $composer->require->{'functional/my-layer'});
    }

    public function test_it_registers_a_layer_bucket_outside_the_project_with_a_relative_url(): void
    {
        $outside = sys_get_temp_dir().'/osdd-outside-'.uniqid();
        $this->app['config']->set('osdd.layers.paths.functional', $outside);

        try {
            $this->artisan('osdd:layer', ['name' => 'functional/my-layer', '--target-path' => $outside, '--generators' => ['service-provider']])
                ->expectsConfirmation('Run composer update now?', 'no')
                ->assertExitCode(0);

            $composer = json_decode($this->app['files']->get($this->composerPath), true);
            $url = end($composer['repositories'])['url'];

            $this->assertStringStartsWith('../', $url);
            $this->assertSame(realpath($outside), realpath($this->app->basePath(substr($url, 0, -2))));
        } finally {
            $this->app['files']->deleteDirectory($outside);
        }
    }

    public function test_it_skips_composer_registration_when_no_composer_json_exists(): void
    {
        $this->app['files']->delete($this->composerPath);

        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
    }

    public function test_it_links_policy_to_model_when_both_are_selected(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', ['model', 'policy'], $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/src/Policies/MyLayerPolicy.php');

        $this->assertFileContains([
            'use Functional\MyLayer\Models\MyLayer;',
            'MyLayer $myLayer',
        ], 'functional/my-layer/src/Policies/MyLayerPolicy.php');
    }

    public function test_it_generates_plain_policy_when_model_not_selected(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', ['policy'], $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/src/Policies/MyLayerPolicy.php');

        $contents = $this->app['files']->get(
            $this->app->basePath('functional/my-layer/src/Policies/MyLayerPolicy.php')
        );

        $this->assertStringNotContainsString('public function viewAny(', $contents);
        $this->assertStringNotContainsString('use Functional\MyLayer\Models\MyLayer;', $contents);
    }

    private function allGenerators(): array
    {
        return [
            'migration',
            'model',
            'factory',
            'seeder',
            'service-provider',
            'test',
            'controller',
            'policy',
            'routes',
        ];
    }

    private function defaultGenerators(): array
    {
        return ['migration', 'model', 'factory', 'service-provider', 'test'];
    }
}
