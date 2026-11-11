<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Cache\Console\CacheTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:cache-table')]
final class CacheTableMakeCommand extends CacheTableCommand
{
    use GeneratesLayerTableMigration;
}
