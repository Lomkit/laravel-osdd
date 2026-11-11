<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TableMakeCommandsTest extends TestCase
{
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
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan($command, ['--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration created successfully.')
            ->assertExitCode(0);
        $provider->register($this->app);
        $this->artisan('migrate')->assertExitCode(0);

        $this->assertTrue(Schema::hasTable($table));
        $this->assertCount(1, $this->layerMigrations($table));
        $this->assertSame([], glob($this->app->databasePath('migrations/*')) ?: []);
    }

    #[DataProvider('tableCommands')]
    public function test_a_second_run_reports_the_existing_migration(string $command, string $table): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan($command, ['--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan($command, ['--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration already exists.')
            ->assertExitCode(1);

        $this->assertCount(1, $this->layerMigrations($table));
    }

    public function test_a_table_created_by_another_layer_is_not_created_again(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->app['files']->put($this->project()->path('functional/test-layer/database/migrations/2026_01_01_000000_create_cache_table.php'), '<?php');

        $this->artisan('osdd:cache-table', ['--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration already exists.')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->project()->path(LayerFixture::PROVIDER_LAYER.'/database/migrations'));
    }

    public function test_the_skeleton_jobs_migration_of_osdd_start_counts_for_the_batches_table(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->app['files']->put($this->project()->path('functional/test-layer/database/migrations/0001_01_01_000002_create_jobs_table.php'), '<?php');

        $this->artisan('osdd:queue-batches-table', ['--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Migration already exists.')
            ->assertExitCode(1);
    }

    /**
     * @return list<string>
     */
    private function layerMigrations(string $table): array
    {
        return glob($this->project()->path(LayerFixture::PROVIDER_LAYER."/database/migrations/*_create_{$table}_table.php")) ?: [];
    }
}
