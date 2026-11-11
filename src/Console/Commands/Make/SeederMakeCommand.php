<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\Statements\LoadSeeders;
use Lomkit\Osdd\Generation\ClassLocation;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:seeder')]
final class SeederMakeCommand extends \Illuminate\Database\Console\Seeds\SeederMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        $this->wireInTargetLayer(new LoadSeeders($this->qualifyClass($this->getNameInput())));
    }

    protected function classLocation(): ClassLocation
    {
        return $this->target()->folder(LayerFolder::Seeders);
    }
}
