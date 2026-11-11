<?php

namespace Lomkit\Osdd\Tests\Console;

use Illuminate\Contracts\Console\Kernel;
use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Console\Command\Command;

class CommandRegistrationTest extends TestCase
{
    private const GENERATOR_COMMANDS = [
        'osdd:cache-table',
        'osdd:cast',
        'osdd:channel',
        'osdd:class',
        'osdd:component',
        'osdd:command',
        'osdd:config',
        'osdd:controller',
        'osdd:enum',
        'osdd:event',
        'osdd:exception',
        'osdd:factory',
        'osdd:interface',
        'osdd:job',
        'osdd:job-middleware',
        'osdd:listener',
        'osdd:mail',
        'osdd:middleware',
        'osdd:migration',
        'osdd:model',
        'osdd:notification',
        'osdd:notifications-table',
        'osdd:observer',
        'osdd:policy',
        'osdd:provider',
        'osdd:queue-batches-table',
        'osdd:queue-failed-table',
        'osdd:queue-table',
        'osdd:request',
        'osdd:resource',
        'osdd:rule',
        'osdd:scope',
        'osdd:seeder',
        'osdd:session-table',
        'osdd:test',
        'osdd:trait',
        'osdd:view',
    ];

    private const NON_GENERATOR_COMMANDS = [
        'osdd:cache',
        'osdd:check',
        'osdd:clear',
        'osdd:graph',
        'osdd:layer',
        'osdd:list',
        'osdd:phpunit',
        'osdd:seed',
        'osdd:start',
    ];

    public function test_it_enumerates_the_command_registry(): void
    {
        $this->artisan('list')->assertExitCode(0);
    }

    public function test_it_registers_every_osdd_command(): void
    {
        $expected = array_merge(self::GENERATOR_COMMANDS, self::NON_GENERATOR_COMMANDS);

        sort($expected);

        $this->assertSame($expected, array_keys($this->osddCommands()));
    }

    public function test_every_osdd_command_resolves_under_the_name_it_is_registered_with(): void
    {
        foreach ($this->osddCommands() as $name => $command) {
            $this->assertSame(
                $name,
                $command->getName(),
                "Command '{$name}' resolves as '{$command->getName()}'.",
            );
        }
    }

    public function test_every_osdd_generator_accepts_a_layer_option(): void
    {
        $commands = $this->osddCommands();

        foreach (self::GENERATOR_COMMANDS as $name) {
            $this->assertTrue(
                $commands[$name]->getDefinition()->hasOption('layer'),
                "Command '{$name}' has no --layer option.",
            );
        }
    }

    public function test_osdd_generators_keep_the_options_inherited_from_their_laravel_counterpart(): void
    {
        $commands = $this->osddCommands();

        $this->assertTrue($commands['osdd:cast']->getDefinition()->hasOption('inbound'));
        $this->assertTrue($commands['osdd:model']->getDefinition()->hasOption('migration'));
        $this->assertTrue($commands['osdd:controller']->getDefinition()->hasOption('requests'));
        $this->assertTrue($commands['osdd:migration']->getDefinition()->hasOption('create'));
        $this->assertTrue($commands['osdd:component']->getDefinition()->hasOption('inline'));
        $this->assertTrue($commands['osdd:component']->getDefinition()->hasOption('view'));
    }

    public function test_the_table_generators_leave_the_framework_aliases_alone(): void
    {
        $all = $this->app->make(Kernel::class)->all();

        foreach (['cache:table' => 'make:cache-table', 'session:table' => 'make:session-table', 'queue:table' => 'make:queue-table', 'queue:failed-table' => 'make:queue-failed-table', 'queue:batches-table' => 'make:queue-batches-table', 'notifications:table' => 'make:notifications-table'] as $alias => $name) {
            $this->assertSame($name, $all[$alias]->getName());
            $this->assertSame([], $all[str_replace('make:', 'osdd:', $name)]->getAliases());
        }
    }

    /**
     * @return array<string, Command>
     */
    private function osddCommands(): array
    {
        $commands = array_filter(
            $this->app->make(Kernel::class)->all(),
            fn (string $name) => str_starts_with($name, 'osdd:'),
            ARRAY_FILTER_USE_KEY,
        );

        ksort($commands);

        return $commands;
    }
}
