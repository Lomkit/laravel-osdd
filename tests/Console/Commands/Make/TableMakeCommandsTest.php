<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\Tests\Concerns\CreatesProviderLayer;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TableMakeCommandsTest extends TestCase
{
    use CreatesProviderLayer;

    private const string EMPTY_PROVIDER = <<<'PHP'
            public function boot(): void
            {
                //
            }
        PHP;

    protected function tearDown(): void
    {
        $this->deleteProviderLayer();

        foreach (glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) ?: [] as $file) {
            $this->app['files']->delete($file);
        }

        parent::tearDown();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function tableCommands(): array
    {
        return [
            'cache' => ['osdd:cache-table', 'cache'],
            'sessions' => ['osdd:session-table', 'sessions'],
            'jobs' => ['osdd:queue-table', 'jobs'],
            'failed jobs' => ['osdd:queue-failed-table', 'failed_jobs'],
            'job batches' => ['osdd:queue-batches-table', 'job_batches'],
            'notifications' => ['osdd:notifications-table', 'notifications'],
        ];
    }

    #[DataProvider('tableCommands')]
    public function test_the_migration_is_written_in_the_layer_and_migrates(string $command, string $table): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);
        $rootMigrations = glob($this->app->databasePath('migrations/*')) ?: [];

        $this->artisan($command, ['--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration created successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, glob($this->app->basePath(self::PROVIDER_LAYER."/database/migrations/*_create_{$table}_table.php")) ?: []);
        $this->assertSame($rootMigrations, glob($this->app->databasePath('migrations/*')) ?: []);
        $this->assertStringContainsString(
            "\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');",
            (string) file_get_contents($provider['path']),
        );

        require_once $provider['path'];
        $this->app->register($provider['class']);
        $this->artisan('migrate')->assertExitCode(0);

        $this->assertTrue(Schema::hasTable($table));
    }

    #[DataProvider('tableCommands')]
    public function test_a_second_run_reports_the_existing_migration(string $command, string $table): void
    {
        $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan($command, ['--layer' => self::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan($command, ['--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration already exists.')
            ->assertExitCode(1);

        $this->assertCount(1, glob($this->app->basePath(self::PROVIDER_LAYER."/database/migrations/*_create_{$table}_table.php")) ?: []);
    }

    public function test_a_table_created_by_another_layer_is_not_created_again(): void
    {
        $this->createProviderLayer(self::EMPTY_PROVIDER);
        $this->app['files']->put($this->app->basePath('functional/test-layer/database/migrations/2026_01_01_000000_create_cache_table.php'), '<?php');

        $this->artisan('osdd:cache-table', ['--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration already exists.')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->app->basePath(self::PROVIDER_LAYER.'/database/migrations'));
    }

    public function test_the_skeleton_jobs_migration_of_osdd_start_counts_for_the_batches_table(): void
    {
        $this->createProviderLayer(self::EMPTY_PROVIDER);
        $this->app['files']->put($this->app->basePath('functional/test-layer/database/migrations/0001_01_01_000002_create_jobs_table.php'), '<?php');

        $this->artisan('osdd:queue-batches-table', ['--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration already exists.')
            ->assertExitCode(1);
    }
}
