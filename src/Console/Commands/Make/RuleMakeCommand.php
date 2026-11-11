<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:rule')]
final class RuleMakeCommand extends \Illuminate\Foundation\Console\RuleMakeCommand
{
    use GeneratesClassesInLayer;
}
