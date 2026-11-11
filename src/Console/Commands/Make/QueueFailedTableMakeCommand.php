<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Queue\Console\FailedTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:queue-failed-table')]
final class QueueFailedTableMakeCommand extends FailedTableCommand
{
    use GeneratesLayerTableMigration;
}
