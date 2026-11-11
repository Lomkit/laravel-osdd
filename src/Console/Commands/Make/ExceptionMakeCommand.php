<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:exception')]
final class ExceptionMakeCommand extends \Illuminate\Foundation\Console\ExceptionMakeCommand
{
    use GeneratesClassesInLayer;
}
