<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Generation\ClassLocation;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:seeder')]
final class SeederMakeCommand extends \Illuminate\Database\Console\Seeds\SeederMakeCommand
{
    use GeneratesClassesInLayer;
    use RegistersInLayerProvider;

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        $seederClass = $this->qualifyClass($this->getNameInput());

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->loadSeeders([\\{$seederClass}::class]);",
            $seederClass,
        );
    }

    protected function classLocation(): ClassLocation
    {
        return $this->target()->folder(LayerFolder::Seeders);
    }
}
