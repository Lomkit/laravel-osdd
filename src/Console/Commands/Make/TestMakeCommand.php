<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\ClassLocation;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:test')]
final class TestMakeCommand extends \Illuminate\Foundation\Console\TestMakeCommand
{
    use GeneratesClassesInLayer;

    protected function classLocation(): ClassLocation
    {
        return $this->target()->folder(LayerFolder::Tests);
    }
}
