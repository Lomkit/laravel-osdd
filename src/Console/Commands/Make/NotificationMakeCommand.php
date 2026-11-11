<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:notification')]
final class NotificationMakeCommand extends \Illuminate\Foundation\Console\NotificationMakeCommand
{
    use GeneratesClassesInLayer;
}
