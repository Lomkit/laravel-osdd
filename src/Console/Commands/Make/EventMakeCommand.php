<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:event')]
final class EventMakeCommand extends \Illuminate\Foundation\Console\EventMakeCommand
{
    use GeneratesClassesInLayer;
}
