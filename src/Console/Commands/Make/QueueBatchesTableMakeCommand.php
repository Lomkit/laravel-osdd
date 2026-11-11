<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Queue\Console\BatchesTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:queue-batches-table')]
final class QueueBatchesTableMakeCommand extends BatchesTableCommand
{
    use GeneratesLayerTableMigration;
}
