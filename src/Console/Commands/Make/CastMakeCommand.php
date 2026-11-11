<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:cast')]
final class CastMakeCommand extends \Illuminate\Foundation\Console\CastMakeCommand
{
    use GeneratesClassesInLayer;
}
