<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Notifications\Console\NotificationTableCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:notifications-table')]
final class NotificationsTableMakeCommand extends NotificationTableCommand
{
    use GeneratesLayerTableMigration;
}
