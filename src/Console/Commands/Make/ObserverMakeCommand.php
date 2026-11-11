<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:observer')]
final class ObserverMakeCommand extends \Illuminate\Foundation\Console\ObserverMakeCommand
{
    use GeneratesClassesInLayer;
}
