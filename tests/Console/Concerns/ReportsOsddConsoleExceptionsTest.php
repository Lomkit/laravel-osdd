<?php

namespace Lomkit\Osdd\Tests\Console\Concerns;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use LogicException;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Layers\LayerNotFoundException;
use Lomkit\Osdd\Tests\TestCase;

class ReportsOsddConsoleExceptionsTest extends TestCase
{
    public function test_an_osdd_console_exception_is_printed_and_fails_the_command(): void
    {
        $this->registerCommand(new class extends Command
        {
            use ReportsOsddConsoleExceptions;

            protected $name = 'osdd-test:reported';

            public function handle(): int
            {
                throw new LayerNotFoundException('functional/billing', ['functional/orders'], null);
            }
        });

        $this->artisan('osdd-test:reported')
            ->expectsOutputToContain('Layer [functional/billing] not found. Available layers: functional/orders.')
            ->assertExitCode(1);
    }

    public function test_any_other_exception_is_not_swallowed(): void
    {
        $this->registerCommand(new class extends Command
        {
            use ReportsOsddConsoleExceptions;

            protected $name = 'osdd-test:unreported';

            public function handle(): int
            {
                throw new LogicException('Unexpected failure.');
            }
        });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unexpected failure.');

        $this->artisan('osdd-test:unreported')->run();
    }

    private function registerCommand(Command $command): void
    {
        $this->app->make(Kernel::class)->registerCommand($command);
    }
}
