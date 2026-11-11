<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Cache\Console\CacheTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:cache-table')]
class CacheTableMakeCommand extends CacheTableCommand
{
    use GeneratesLayerTableMigration;

    protected $name = 'osdd:cache-table';

    /**
     * @var list<string>
     */
    protected $aliases = [];
}
