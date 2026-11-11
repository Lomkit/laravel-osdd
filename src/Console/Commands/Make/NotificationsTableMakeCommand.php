<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Notifications\Console\NotificationTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:notifications-table')]
class NotificationsTableMakeCommand extends NotificationTableCommand
{
    use GeneratesLayerTableMigration;

    protected $name = 'osdd:notifications-table';

    /**
     * @var list<string>
     */
    protected $aliases = [];
}
