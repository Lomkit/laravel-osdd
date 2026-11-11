<?php

namespace Lomkit\Osdd\Tests\Generation;

use Lomkit\Osdd\Console\Commands\Make\CastMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\SessionTableMakeCommand;
use Lomkit\Osdd\Generation\CommandName;
use Lomkit\Osdd\Generation\CommandNameNotDeclaredException;
use Lomkit\Osdd\Tests\TestCase;

class CommandNameTest extends TestCase
{
    public function test_it_reads_the_name_declared_by_as_command(): void
    {
        $this->assertSame('osdd:cast', CommandName::of(CastMakeCommand::class));
        $this->assertSame('osdd:session-table', CommandName::of(SessionTableMakeCommand::class));
    }

    public function test_a_class_without_as_command_has_no_name(): void
    {
        $this->expectException(CommandNameNotDeclaredException::class);
        $this->expectExceptionMessage('Command [Lomkit\Osdd\Tests\TestCase] declares no #[AsCommand] name.');

        CommandName::of(TestCase::class);
    }
}
