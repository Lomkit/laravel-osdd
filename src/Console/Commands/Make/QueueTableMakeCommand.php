<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Queue\Console\TableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:queue-table')]
final class QueueTableMakeCommand extends TableCommand
{
    use GeneratesLayerTableMigration;
}
