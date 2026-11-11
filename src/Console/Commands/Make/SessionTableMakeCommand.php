<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Session\Console\SessionTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:session-table')]
final class SessionTableMakeCommand extends SessionTableCommand
{
    use GeneratesLayerTableMigration;
}
