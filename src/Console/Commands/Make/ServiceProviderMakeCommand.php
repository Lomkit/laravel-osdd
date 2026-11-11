<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Lomkit\Osdd\Composer\LayerComposerJson;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\RefreshLayers;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:provider')]
final class ServiceProviderMakeCommand extends ProviderMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    public function handle(): ?bool
    {
        if (GeneratorCommand::handle() === false) {
            return false;
        }

        $this->registerProviderInComposer();

        return null;
    }

    private function registerProviderInComposer(): void
    {
        $composerPath = $this->target()->layer->directory->manifestFile();
        $providerClass = $this->qualifyClass($this->getNameInput());

        $this->laravel->make(LayerComposerJson::class)->declareProvider($composerPath, $providerClass);

        $this->laravel->make(RefreshLayers::class)();

        $this->components->info("Provider [{$providerClass}] registered in [{$composerPath}].");
    }
}
