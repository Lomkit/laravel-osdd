<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class MigrateMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    private string $providerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerPath = $this->app->basePath('functional/test-layer/src/Providers/TestLayerServiceProvider.php');

        $this->app['files']->ensureDirectoryExists(dirname($this->providerPath));

        file_put_contents($this->providerPath, <<<'PHP'
<?php

namespace Functional\TestLayer\Providers;

use Lomkit\Osdd\LayerServiceProvider;

class TestLayerServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }
}
PHP);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) as $file) {
            $this->app['files']->delete($file);
        }

        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        parent::tearDown();
    }

    public function test_it_generates_migration_file_in_correct_path(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }

    public function test_it_generates_migration_with_create_option(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_posts_table', '--create' => 'posts', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_posts_table.php'));
        $this->assertNotEmpty($files);
    }

    public function test_it_registers_migrations_path_in_service_provider(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ["\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');"],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function test_registration_is_idempotent(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])->assertExitCode(0);
        $this->artisan('osdd:migration', ['name' => 'create_posts_table', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $content = file_get_contents($this->providerPath);
        $count = substr_count($content, 'loadMigrationsFrom(__DIR__');

        $this->assertSame(1, $count);
    }

    public function test_it_skips_registration_when_service_provider_does_not_exist(): void
    {
        $this->app['files']->delete($this->providerPath);

        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }
}
