<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:command')]
final class ConsoleMakeCommand extends \Illuminate\Foundation\Console\ConsoleMakeCommand
{
    use GeneratesClassesInLayer;
    use RegistersInLayerProvider;

    #[\Override]
    public function handle(): ?bool
    {
        $created = parent::handle();

        $commandClass = $this->qualifyClass($this->getNameInput());

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->commands([\\{$commandClass}::class]);",
            $commandClass,
        );

        return $created;
    }
}
