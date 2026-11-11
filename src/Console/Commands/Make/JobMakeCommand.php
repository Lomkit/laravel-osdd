<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:job')]
final class JobMakeCommand extends \Illuminate\Foundation\Console\JobMakeCommand
{
    use GeneratesClassesInLayer;
}
