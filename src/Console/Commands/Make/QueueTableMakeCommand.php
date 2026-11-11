<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Queue\Console\TableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:queue-table')]
class QueueTableMakeCommand extends TableCommand
{
    use GeneratesLayerTableMigration;

    protected $name = 'osdd:queue-table';

    /**
     * @var list<string>
     */
    protected $aliases = [];
}
