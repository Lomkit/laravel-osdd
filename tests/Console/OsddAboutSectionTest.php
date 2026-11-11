<?php

namespace Lomkit\Osdd\Tests\Console;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Tests\TestCase;

class OsddAboutSectionTest extends TestCase
{
    public function test_it_lists_the_layers_the_migration_paths_and_the_seeder_count(): void
    {
        $this->app->instance('migrator', $this->emptyMigrator());
        $this->app->make('migrator')->path($this->app->basePath('functional/test-layer/database/migrations'));
        $this->app->make(SeederRegistry::class)->push(0, 'Functional\\TestLayer\\Database\\Seeders\\TestLayerSeeder', 'Functional\\TestLayer\\Database\\Seeders\\DemoSeeder');

        $this->assertSame([
            'layers' => '1',
            'functional/test-layer' => 'functional/test-layer',
            'migration_paths' => 'functional/test-layer/database/migrations',
            'seeders' => '2',
        ], $this->aboutOsdd());
    }

    public function test_it_reports_an_application_without_layers(): void
    {
        $this->app['config']->set('osdd.layers.paths', []);
        $this->app->instance('migrator', $this->emptyMigrator());

        $this->assertSame([
            'layers' => '0',
            'migration_paths' => 'none',
            'seeders' => '0',
        ], $this->aboutOsdd());
    }

    public function test_it_shows_an_osdd_section(): void
    {
        $this->artisan('about', ['--only' => 'osdd'])
            ->expectsOutputToContain('OSDD')
            ->expectsOutputToContain('functional/test-layer')
            ->assertExitCode(0);
    }

    /**
     * @return array<string, string>
     */
    private function aboutOsdd(): array
    {
        Artisan::call('about', ['--only' => 'osdd', '--json' => true]);

        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)['o_s_d_d'];
    }

    private function emptyMigrator(): Migrator
    {
        return new Migrator($this->app['migration.repository'], $this->app['db'], $this->app['files']);
    }
}
