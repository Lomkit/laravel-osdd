<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Tests\TestCase;

class SeedCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingSeeder::$connections = [];
        SecondRecordingSeeder::$connections = [];
    }

    public function test_it_runs_successfully_when_no_seeders_are_registered(): void
    {
        $this->artisan('osdd:seed')->assertExitCode(0);
    }

    public function test_it_warns_when_no_seeders_are_registered(): void
    {
        $this->artisan('osdd:seed')
            ->expectsOutputToContain('No OSDD seeders registered')
            ->assertExitCode(0);
    }

    public function test_it_skips_non_existent_seeder_classes(): void
    {
        $this->app->make(SeederRegistry::class)->push(0, 'NonExistent\\Seeder');

        $this->artisan('osdd:seed')->assertExitCode(0);
    }

    public function test_seeders_are_returned_in_priority_order(): void
    {
        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(10, 'Seeder\\C');
        $registry->push(-5, 'Seeder\\A');
        $registry->push(0, 'Seeder\\B');

        $this->assertSame(['Seeder\\A', 'Seeder\\B', 'Seeder\\C'], $registry->seeders());
    }

    public function test_seeders_with_same_priority_preserve_registration_order(): void
    {
        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(0, 'Seeder\\First');
        $registry->push(0, 'Seeder\\Second');

        $this->assertSame(['Seeder\\First', 'Seeder\\Second'], $registry->seeders());
    }

    public function test_a_seeder_registered_twice_runs_once_with_its_first_priority(): void
    {
        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(5, 'Seeder\\Orders');
        $registry->push(0, 'Seeder\\Users');
        $registry->push(-10, '\\Seeder\\Orders');

        $this->assertSame(['Seeder\\Users', 'Seeder\\Orders'], $registry->seeders());
    }

    public function test_it_runs_migrate_fresh_when_fresh_option_is_given(): void
    {
        $this->artisan('osdd:seed --fresh')->assertExitCode(0);
    }

    public function test_it_runs_migrate_refresh_when_refresh_option_is_given(): void
    {
        $this->artisan('osdd:seed --refresh')->assertExitCode(0);
    }

    public function test_fresh_and_refresh_are_mutually_exclusive(): void
    {
        $this->artisan('osdd:seed --fresh --refresh')
            ->expectsOutputToContain('Pass either --fresh or --refresh, not both.')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_seeding_is_cancelled_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->app->make(SeederRegistry::class)->push(0, RecordingSeeder::class);

        $this->artisan('osdd:seed', ['--no-interaction' => true])->assertExitCode(1);

        $this->assertSame([], RecordingSeeder::$connections);
    }

    public function test_it_stops_at_the_first_failing_seeder(): void
    {
        $this->app['env'] = 'production';
        $registry = $this->app->make(SeederRegistry::class);
        $registry->push(0, RecordingSeeder::class);
        $registry->push(1, SecondRecordingSeeder::class);

        $this->artisan('osdd:seed', ['--no-interaction' => true])
            ->expectsOutputToContain('the next seeders were not run')
            ->assertExitCode(1);

        $this->assertSame([], RecordingSeeder::$connections);
        $this->assertSame([], SecondRecordingSeeder::$connections);
    }

    public function test_force_seeds_in_production_without_asking(): void
    {
        $this->app['env'] = 'production';
        $this->app->make(SeederRegistry::class)->push(0, RecordingSeeder::class);

        $this->artisan('osdd:seed', ['--force' => true, '--no-interaction' => true])->assertExitCode(0);

        $this->assertSame(['testing'], RecordingSeeder::$connections);
    }

    public function test_it_seeds_the_given_database(): void
    {
        $this->app['config']->set('database.connections.secondary', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $this->app->make(SeederRegistry::class)->push(0, RecordingSeeder::class);

        $this->artisan('osdd:seed', ['--database' => 'secondary'])->assertExitCode(0);

        $this->assertSame(['secondary'], RecordingSeeder::$connections);
    }
}

class RecordingSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public static array $connections = [];

    public function run(): void
    {
        static::$connections[] = (string) DB::connection()->getName();
    }
}

class SecondRecordingSeeder extends RecordingSeeder
{
    /**
     * @var list<string>
     */
    public static array $connections = [];
}
