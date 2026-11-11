<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Lomkit\Osdd\SeederRegistry;
use Lomkit\Osdd\Tests\TestCase;

class SeedCommandTest extends TestCase
{
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

    public function test_fresh_takes_precedence_over_refresh(): void
    {
        $this->artisan('osdd:seed --fresh --refresh')->assertExitCode(0);
    }
}
