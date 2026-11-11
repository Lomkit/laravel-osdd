<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:resource')]
final class ResourceMakeCommand extends \Illuminate\Foundation\Console\ResourceMakeCommand
{
    use GeneratesClassesInLayer;
}
