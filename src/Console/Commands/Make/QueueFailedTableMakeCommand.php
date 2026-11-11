<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Queue\Console\FailedTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:queue-failed-table')]
class QueueFailedTableMakeCommand extends FailedTableCommand
{
    use GeneratesLayerTableMigration;

    protected $name = 'osdd:queue-failed-table';

    /**
     * @var list<string>
     */
    protected $aliases = [];
}
