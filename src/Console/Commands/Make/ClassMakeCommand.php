<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:class')]
final class ClassMakeCommand extends \Illuminate\Foundation\Console\ClassMakeCommand
{
    use GeneratesClassesInLayer;
}
