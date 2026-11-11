<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Functional\Users\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\Seeders\LayersSeeder;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class StartCommandTest extends TestCase
{
    private const string DELETE_QUESTION = 'osdd:start deletes app/, config/ and database/ (database/database.sqlite is kept). Continue?';

    protected string $projectPath;

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = $this->project()->root;

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->projectPath.'/functional',
            'technical' => $this->projectPath.'/technical',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $files = $this->app['files'];
        $files->ensureDirectoryExists($this->projectPath.'/app');
        $files->ensureDirectoryExists($this->projectPath.'/database/factories');
        $files->put($this->projectPath.'/composer.json', json_encode($this->fakeComposer(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        $files->put($this->projectPath.'/config/app.php', '<?php return [];');
        $files->put($this->projectPath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
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

    public function test_the_users_service_provider_places_its_statements_in_the_stub(): void
    {
        $this->runStart();

        $this->assertSame(<<<'PHP'
            <?php

            namespace Functional\Users\Providers;

            use Functional\Users\Database\Seeders\UsersSeeder;
            use Functional\Users\Models\User;
            use Lomkit\Osdd\LayerServiceProvider;

            class UsersServiceProvider extends LayerServiceProvider
            {
                public function boot(): void
                {
                    if ($this->app->runningInConsole()) {
                        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
                        $this->loadSeeders([UsersSeeder::class]);
                    }
                }

                public function register(): void
                {
                    config(['auth.providers.users.model' => User::class]);
                }
            }

            PHP, file_get_contents($this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php'));
    }

    public function test_the_users_seeder_generated_again_stays_registered_once(): void
    {
        $this->runStart();

        $this->artisan('osdd:seeder', ['name' => 'UsersSeeder', '--layer' => 'functional/users'])->assertExitCode(0);

        $this->assertSame(1, substr_count((string) file_get_contents($this->projectPath.'/functional/users/src/Providers/UsersServiceProvider.php'), 'loadSeeders('));
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
        $this->app['files']->deleteDirectory($this->projectPath.'/bootstrap');

        $this->runStart();
    }

    public function test_it_creates_the_osdd_layer(): void
    {
        $this->runStart();

        $this->assertFileExists($this->projectPath.'/technical/osdd/composer.json');
        $this->assertFileExists($this->projectPath.'/technical/osdd/config/osdd.php');
        $this->assertFileExists($this->projectPath.'/technical/osdd/src/Providers/OsddServiceProvider.php');
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
        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $user);
        $this->assertInstanceOf('Functional\Users\Database\Factories\UserFactory', User::factory());
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

        $this->assertTrue(Schema::connection('sqlite')->hasColumns('users', ['name', 'email', 'password', 'remember_token']));
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

    public function test_the_manifests_declare_both_layers_and_the_project_requires_them(): void
    {
        $this->runStart();

        $users = $this->decodeJson('functional/users/composer.json');
        $osdd = $this->decodeJson('technical/osdd/composer.json');
        $root = $this->decodeJson('composer.json');

        $this->assertSame(['functional/users', 'layer', '1.0.0'], [$users['name'], $users['type'], $users['version']]);
        $this->assertSame([
            'Functional\\Users\\' => 'src/',
            'Functional\\Users\\Database\\Seeders\\' => 'database/seeders/',
            'Functional\\Users\\Database\\Factories\\' => 'database/factories/',
        ], $users['autoload']['psr-4']);
        $this->assertSame(['Functional\\Users\\Providers\\UsersServiceProvider'], $users['extra']['laravel']['providers']);
        $this->assertSame(['technical/osdd', 'layer', '1.0.0'], [$osdd['name'], $osdd['type'], $osdd['version']]);
        $this->assertSame('src/', $osdd['autoload']['psr-4']['Technical\\Osdd\\']);
        $this->assertSame(['Technical\\Osdd\\Providers\\OsddServiceProvider'], $osdd['extra']['laravel']['providers']);
        $this->assertSame(['functional/users' => '*', 'technical/osdd' => '*'], $root['require']);
        $this->assertEqualsCanonicalizing(
            [['type' => 'path', 'url' => './functional/*'], ['type' => 'path', 'url' => './technical/*']],
            $root['repositories'],
        );
    }

    public function test_the_generated_providers_boot_the_users_and_osdd_layers(): void
    {
        $this->runStart();
        $this->app['config']->set('osdd.layers.paths', ['functional' => '/elsewhere']);

        foreach ([
            'functional/users/src/Providers/UsersServiceProvider.php' => 'Functional\Users\Providers\UsersServiceProvider',
            'technical/osdd/src/Providers/OsddServiceProvider.php' => 'Technical\Osdd\Providers\OsddServiceProvider',
        ] as $file => $class) {
            $provider = GeneratedClass::load($this->projectPath.'/'.$file, $class);
            $this->assertTrue($provider->isSubclassOf(LayerServiceProvider::class));
            $this->app->register($class);
        }

        $this->assertSame(User::class, $this->app['config']->get('auth.providers.users.model'));
        $this->assertSame(['Functional\Users\Database\Seeders\UsersSeeder'], $this->app->make(SeederRegistry::class)->seeders());
        $this->assertInstanceOf(LayersSeeder::class, $this->app->make('DatabaseSeeder'));
        $this->assertSame(
            ['functional' => $this->projectPath.'/functional', 'technical' => $this->projectPath.'/technical'],
            $this->app['config']->get('osdd.layers.paths'),
        );
        $this->assertContains($this->projectPath.'/functional/users/database/migrations', array_map('realpath', $this->app['migrator']->paths()));
        $this->assertContains($this->projectPath.'/technical/osdd/database/migrations', array_map('realpath', $this->app['migrator']->paths()));
    }

    private function runStart(): void
    {
        $this->artisan('osdd:start', ['--force' => true, '--no-composer-update' => true])->assertExitCode(0);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $file): array
    {
        return json_decode((string) file_get_contents($this->projectPath.'/'.$file), true);
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
