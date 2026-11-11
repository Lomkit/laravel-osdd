<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class SeederMakeCommandTest extends TestCase
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
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/seeders'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        parent::tearDown();
    }

    public function test_it_generates_seeder_file_in_correct_path(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function test_it_generates_seeder_with_correct_namespace(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Seeders;',
            'class UserSeeder extends Seeder',
        ], 'functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function test_it_generates_nested_seeder_in_correct_path(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'Admin/AdminSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/Admin/AdminSeeder.php');
    }

    public function test_it_generates_nested_seeder_with_correct_namespace(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'Admin/AdminSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Seeders\Admin;',
            'class AdminSeeder extends Seeder',
        ], 'functional/test-layer/database/seeders/Admin/AdminSeeder.php');
    }

    public function test_it_registers_seeder_in_service_provider(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ['$this->loadSeeders([\Functional\TestLayer\Database\Seeders\UserSeeder::class]);'],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function test_it_registers_nested_seeder_with_qualified_class(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'Admin/AdminSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ['$this->loadSeeders([\Functional\TestLayer\Database\Seeders\Admin\AdminSeeder::class]);'],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function test_registration_is_idempotent(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer']);
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer']);

        $content = file_get_contents($this->providerPath);
        $count = substr_count($content, 'Database\Seeders\UserSeeder::class');

        $this->assertSame(1, $count);
    }

    public function test_it_skips_registration_when_service_provider_does_not_exist(): void
    {
        $this->app['files']->delete($this->providerPath);

        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }
}
