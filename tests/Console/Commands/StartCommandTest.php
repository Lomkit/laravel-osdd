<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Functional\Users\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class StartCommandTest extends TestCase
{
    private const string DELETE_QUESTION = 'osdd:start deletes app/, config/ and database/ (database/database.sqlite is kept). Continue?';

    protected string $projectPath;

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = sys_get_temp_dir().'/osdd-start-test-'.uniqid();
        mkdir($this->projectPath, 0755, true);

        $app->setBasePath($this->projectPath);

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->projectPath.'/functional',
            'technical' => $this->projectPath.'/technical',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        mkdir($this->projectPath.'/app', 0755, true);
        mkdir($this->projectPath.'/database/migrations', 0755, true);
        mkdir($this->projectPath.'/database/factories', 0755, true);

        file_put_contents($this->projectPath.'/composer.json', json_encode($this->fakeComposer(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        mkdir($this->projectPath.'/config', 0755, true);
        file_put_contents($this->projectPath.'/config/app.php', '<?php return [];');

        mkdir($this->projectPath.'/bootstrap', 0755, true);
        file_put_contents($this->projectPath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->projectPath);
    }

    public function test_it_creates_the_users_layer(): void
    {
        $this->runStart();

        $this->assertFileExists($this->projectPath.'/functional/users/composer.json');
        $this->assertFileExists($this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php');
        $this->assertDirectoryExists($this->projectPath.'/functional/users/database/migrations');
        $this->assertDirectoryExists($this->projectPath.'/functional/users/database/factories');
        $this->assertFileExists($this->projectPath.'/functional/users/database/seeders/UsersSeeder.php');
        $this->assertDirectoryExists($this->projectPath.'/functional/users/src/Models');
    }

    public function test_it_creates_the_users_layer_composer_json(): void
    {
        $this->runStart();

        $contents = file_get_contents($this->projectPath.'/functional/users/composer.json');

        $this->assertStringContainsString('"name": "functional/users"', $contents);
        $this->assertStringContainsString('"type": "layer"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\": "src/"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\Database\\\\Seeders\\\\": "database/seeders/"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\Database\\\\Factories\\\\": "database/factories/"', $contents);
        $this->assertStringContainsString('Functional\\\\Users\\\\Providers\\\\UsersServiceProvider', $contents);
    }

    public function test_it_creates_user_model_from_stub(): void
    {
        $this->runStart();

        $this->assertFileExists($this->projectPath.'/functional/users/src/Models/User.php');

        $contents = file_get_contents($this->projectPath.'/functional/users/src/Models/User.php');

        $this->assertStringContainsString('namespace Functional\Users\Models;', $contents);
        $this->assertStringContainsString('class User extends Authenticatable', $contents);
        $this->assertStringContainsString('use Functional\Users\Database\Factories\UserFactory;', $contents);
        $this->assertStringContainsString('#[UseFactory(UserFactory::class)]', $contents);
    }

    public function test_it_creates_user_factory_from_stub(): void
    {
        $this->runStart();

        $this->assertFileExists($this->projectPath.'/functional/users/database/factories/UserFactory.php');

        $contents = file_get_contents($this->projectPath.'/functional/users/database/factories/UserFactory.php');

        $this->assertStringContainsString('namespace Functional\Users\Database\Factories;', $contents);
        $this->assertStringContainsString('use Functional\Users\Models\User;', $contents);
        $this->assertStringContainsString('class UserFactory extends Factory', $contents);
    }

    public function test_it_generates_users_seeder_for_users_layer(): void
    {
        $this->runStart();

        $contents = file_get_contents($this->projectPath.'/functional/users/database/seeders/UsersSeeder.php');

        $this->assertStringContainsString('namespace Functional\Users\Database\Seeders;', $contents);
        $this->assertStringContainsString('class UsersSeeder extends Seeder', $contents);
        $this->assertStringContainsString('use Functional\Users\Models\User;', $contents);
        $this->assertStringContainsString('User::factory()->count(10)->create();', $contents);
    }

    public function test_it_creates_user_migration_from_stub(): void
    {
        $this->runStart();

        $migrations = glob($this->projectPath.'/functional/users/database/migrations/*_create_users_table.php');

        $this->assertNotEmpty($migrations);

        $contents = file_get_contents($migrations[0]);

        $this->assertStringContainsString("Schema::create('users'", $contents);
        $this->assertStringContainsString('$table->string(\'email\')', $contents);
    }

    public function test_it_creates_the_users_service_provider(): void
    {
        $this->runStart();

        $contents = file_get_contents($this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php');

        $this->assertStringContainsString('namespace Functional\Users\Providers;', $contents);
        $this->assertStringContainsString('use Lomkit\Osdd\LayerServiceProvider;', $contents);
        $this->assertStringContainsString('class UsersServiceProvider extends LayerServiceProvider', $contents);
        $this->assertStringContainsString("loadMigrationsFrom(__DIR__ . '/../../database/migrations')", $contents);
        $this->assertStringContainsString('loadSeeders([UsersSeeder::class])', $contents);
    }

    public function test_the_generated_providers_extend_the_package_layer_service_provider(): void
    {
        $this->runStart();

        require_once $this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php';
        require_once $this->projectPath.'/technical/osdd/src/Providers/OsddServiceProvider.php';

        $this->assertTrue(is_subclass_of('Functional\Users\Providers\UsersServiceProvider', LayerServiceProvider::class));
        $this->assertTrue(is_subclass_of('Technical\Osdd\Providers\OsddServiceProvider', LayerServiceProvider::class));
    }

    public function test_it_rebinds_the_auth_user_model_to_the_users_layer(): void
    {
        $this->runStart();

        $contents = file_get_contents($this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php');

        $this->assertStringContainsString('use Functional\Users\Models\User;', $contents);
        $this->assertStringContainsString("config(['auth.providers.users.model' => User::class]);", $contents);
    }

    public function test_it_deletes_the_app_directory(): void
    {
        $this->runStart();

        $this->assertDirectoryDoesNotExist($this->projectPath.'/app');
    }

    public function test_it_deletes_the_database_directory(): void
    {
        $this->runStart();

        $this->assertDirectoryDoesNotExist($this->projectPath.'/database');
    }

    public function test_it_deletes_the_config_directory(): void
    {
        $this->runStart();

        $this->assertDirectoryDoesNotExist($this->projectPath.'/config');
    }

    public function test_it_uses_the_configured_functional_path(): void
    {
        $custom = $this->projectPath.'/layers/functional';
        $this->app['config']->set('osdd.layers.paths', [
            'functional' => $custom,
            'technical' => $this->projectPath.'/technical',
        ]);

        $this->runStart();

        $this->assertFileExists($custom.'/users/composer.json');
    }

    public function test_it_removes_the_app_providers_from_bootstrap_providers(): void
    {
        file_put_contents($this->projectPath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n    App\\Providers\\TelescopeServiceProvider::class,\n    Spatie\\Permission\\PermissionServiceProvider::class,\n];\n");

        $this->runStart();

        $this->assertSame(
            ['Spatie\\Permission\\PermissionServiceProvider'],
            require $this->projectPath.'/bootstrap/providers.php',
        );
    }

    public function test_it_skips_bootstrap_providers_gracefully_when_missing(): void
    {
        unlink($this->projectPath.'/bootstrap/providers.php');
        rmdir($this->projectPath.'/bootstrap');

        $this->runStart();
    }

    public function test_it_registers_users_layer_as_path_repository_in_composer_json(): void
    {
        $this->runStart();

        $composer = json_decode(file_get_contents($this->projectPath.'/composer.json'), true);

        $urls = array_column($composer['repositories'] ?? [], 'url');
        $this->assertContains('./functional/*', $urls);

        $types = array_column($composer['repositories'], 'type');
        $this->assertContains('path', $types);
    }

    public function test_it_adds_users_layer_to_require_in_composer_json(): void
    {
        $this->runStart();

        $composer = json_decode(file_get_contents($this->projectPath.'/composer.json'), true);

        $this->assertArrayHasKey('functional/users', $composer['require']);
        $this->assertSame('*', $composer['require']['functional/users']);
    }

    public function test_it_creates_the_osdd_layer(): void
    {
        $this->runStart();

        $this->assertFileExists($this->projectPath.'/technical/osdd/composer.json');
        $this->assertFileExists($this->projectPath.'/technical/osdd/config/osdd.php');
        $this->assertFileExists($this->projectPath.'/technical/osdd/src/Providers/OsddServiceProvider.php');
    }

    public function test_it_creates_the_osdd_layer_composer_json(): void
    {
        $this->runStart();

        $contents = file_get_contents($this->projectPath.'/technical/osdd/composer.json');

        $this->assertStringContainsString('"name": "technical/osdd"', $contents);
        $this->assertStringContainsString('"type": "layer"', $contents);
        $this->assertStringContainsString('"version": "1.0.0"', $contents);
        $this->assertStringContainsString('"Technical\\\\Osdd\\\\": "src/"', $contents);
        $this->assertStringContainsString('OsddServiceProvider', $contents);
    }

    public function test_it_creates_the_osdd_service_provider(): void
    {
        $this->runStart();

        $contents = file_get_contents($this->projectPath.'/technical/osdd/src/Providers/OsddServiceProvider.php');

        $this->assertStringContainsString('namespace Technical\Osdd\Providers;', $contents);
        $this->assertStringContainsString('use Lomkit\Osdd\LayerServiceProvider;', $contents);
        $this->assertStringContainsString('class OsddServiceProvider extends LayerServiceProvider', $contents);
        $this->assertStringContainsString('overrideConfigFrom', $contents);
    }

    public function test_it_registers_osdd_layer_as_path_repository_in_composer_json(): void
    {
        $this->runStart();

        $composer = json_decode(file_get_contents($this->projectPath.'/composer.json'), true);

        $urls = array_column($composer['repositories'] ?? [], 'url');
        $this->assertContains('./technical/*', $urls);
    }

    public function test_it_adds_osdd_layer_to_require_in_composer_json(): void
    {
        $this->runStart();

        $composer = json_decode(file_get_contents($this->projectPath.'/composer.json'), true);

        $this->assertArrayHasKey('technical/osdd', $composer['require']);
        $this->assertSame('*', $composer['require']['technical/osdd']);
    }

    public function test_it_cleans_legacy_autoload_from_composer_json(): void
    {
        $this->runStart();

        $composer = json_decode(file_get_contents($this->projectPath.'/composer.json'), true);

        $psr4 = $composer['autoload']['psr-4'] ?? [];
        $this->assertArrayNotHasKey('Database\\Factories\\', $psr4);
        $this->assertArrayNotHasKey('Database\\Seeders\\', $psr4);

        // The App\ mapping is kept so Laravel can still detect the application
        // namespace once app/ is deleted.
        $this->assertArrayHasKey('App\\', $psr4);
        $this->assertSame('app/', $psr4['App\\']);
    }

    public function test_it_asks_before_deleting_then_for_composer_update(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation(self::DELETE_QUESTION, 'yes')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->projectPath.'/app');
    }

    public function test_the_user_model_fills_and_hides_the_expected_attributes(): void
    {
        $this->runStart();

        require_once $this->projectPath.'/functional/users/database/factories/UserFactory.php';
        require_once $this->projectPath.'/functional/users/src/Models/User.php';

        $user = new User([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
            'remember_token' => 'remember-me',
        ]);
        $user->setRememberToken('remember-me');

        $this->assertSame(['name', 'email', 'password'], $user->getFillable());
        $this->assertSame(['password', 'remember_token'], $user->getHidden());
        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $this->assertSame(['name' => 'Ada Lovelace', 'email' => 'ada@example.com'], $user->toArray());
    }

    #[RunInSeparateProcess]
    public function test_the_application_migrates_into_the_kept_sqlite_database(): void
    {
        touch($this->projectPath.'/database/database.sqlite');

        $this->runStart();

        $this->assertFileExists($this->projectPath.'/database/database.sqlite');

        require_once $this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php';
        require_once $this->projectPath.'/technical/osdd/src/Providers/OsddServiceProvider.php';
        $this->app->register('Functional\\Users\\Providers\\UsersServiceProvider');
        $this->app->register('Technical\\Osdd\\Providers\\OsddServiceProvider');

        $defaults = require dirname((string) (new \ReflectionClass(Application::class))->getFileName(), 4).'/config/database.php';
        $this->app['config']->set('database.connections.sqlite', $defaults['connections']['sqlite']);

        $this->artisan('migrate', ['--database' => 'sqlite'])->assertExitCode(0);

        $this->assertSame($this->projectPath.'/database/database.sqlite', $this->app['db']->connection('sqlite')->getDatabaseName());

        foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertTrue(Schema::connection('sqlite')->hasTable($table), "Table [{$table}] was not created.");
        }
    }

    #[RunInSeparateProcess]
    public function test_the_default_seeder_runs_the_layer_seeders(): void
    {
        $this->runStart();

        foreach (['src/Models/User.php', 'database/factories/UserFactory.php', 'database/seeders/UsersSeeder.php', 'src/Providers/UsersServiceProvider.php'] as $file) {
            require_once $this->projectPath.'/functional/users/'.$file;
        }

        require_once $this->projectPath.'/technical/osdd/src/Providers/OsddServiceProvider.php';
        $this->app->register('Functional\\Users\\Providers\\UsersServiceProvider');
        $this->app->register('Technical\\Osdd\\Providers\\OsddServiceProvider');

        $this->assertFalse(class_exists('Database\\Seeders\\DatabaseSeeder'));

        $this->artisan('migrate', ['--seed' => true])->assertExitCode(0);

        $this->assertSame(10, User::query()->count());
    }

    public function test_it_keeps_the_skeleton_migration_names(): void
    {
        $this->runStart();

        $this->assertFileExists($this->projectPath.'/functional/users/database/migrations/0001_01_01_000000_create_users_table.php');
        $this->assertFileExists($this->projectPath.'/technical/osdd/database/migrations/0001_01_01_000001_create_cache_table.php');
        $this->assertFileExists($this->projectPath.'/technical/osdd/database/migrations/0001_01_01_000002_create_jobs_table.php');
    }

    public function test_it_empties_the_database_directory_but_keeps_the_sqlite_file(): void
    {
        file_put_contents($this->projectPath.'/database/database.sqlite', '');
        file_put_contents($this->projectPath.'/database/.gitignore', "*.sqlite*\n");
        file_put_contents($this->projectPath.'/database/migrations/0001_01_01_000000_create_users_table.php', '<?php');

        $this->runStart();

        $this->assertSame(['.gitignore', 'database.sqlite'], array_values(array_diff(scandir($this->projectPath.'/database'), ['.', '..'])));
    }

    public function test_it_refuses_to_run_twice(): void
    {
        $this->runStart();
        mkdir($this->projectPath.'/app');

        $this->artisan('osdd:start', ['--force' => true, '--no-composer-update' => true])
            ->expectsOutputToContain('osdd:start has already run on this project')
            ->assertExitCode(1);

        $this->assertDirectoryExists($this->projectPath.'/app');
        $this->assertCount(1, glob($this->projectPath.'/functional/users/database/migrations/*_create_users_table.php'));
    }

    public function test_it_refuses_when_the_osdd_layer_exists(): void
    {
        mkdir($this->projectPath.'/technical/osdd', 0755, true);

        $this->artisan('osdd:start', ['--force' => true, '--no-composer-update' => true])
            ->assertExitCode(1);

        $this->assertDirectoryExists($this->projectPath.'/app');
        $this->assertDirectoryDoesNotExist($this->projectPath.'/functional/users');
    }

    public function test_it_changes_nothing_when_the_deletion_is_declined(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation(self::DELETE_QUESTION, 'no')
            ->assertExitCode(1);

        $this->assertDirectoryExists($this->projectPath.'/app');
        $this->assertDirectoryExists($this->projectPath.'/config');
        $this->assertDirectoryDoesNotExist($this->projectPath.'/functional/users');
    }

    public function test_it_requires_force_when_not_interactive(): void
    {
        $this->artisan('osdd:start', ['--no-interaction' => true])
            ->expectsOutputToContain('Pass --force to run it without interaction')
            ->assertExitCode(1);

        $this->assertDirectoryExists($this->projectPath.'/app');
        $this->assertDirectoryDoesNotExist($this->projectPath.'/functional/users');
    }

    public function test_force_runs_without_interaction_and_skips_composer_update(): void
    {
        $this->artisan('osdd:start', ['--force' => true, '--no-interaction' => true])
            ->expectsOutputToContain('composer update functional/users technical/osdd')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->projectPath.'/app');
    }

    // -------------------------------------------------------------------------

    private function runStart(): void
    {
        $this->artisan('osdd:start', ['--force' => true, '--no-composer-update' => true])->assertExitCode(0);
    }

    private function fakeComposer(): array
    {
        return [
            'require' => new \stdClass,
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Database\\Factories\\' => 'database/factories/',
                    'Database\\Seeders\\' => 'database/seeders/',
                ],
            ],
        ];
    }
}
