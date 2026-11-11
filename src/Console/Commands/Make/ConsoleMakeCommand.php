<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\Statements\RegisterCommand;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:command')]
final class ConsoleMakeCommand extends \Illuminate\Foundation\Console\ConsoleMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    public function handle(): ?bool
    {
        $created = parent::handle();

        $this->wireInTargetLayer(new RegisterCommand($this->qualifyClass($this->getNameInput())));

        return $created;
    }
}
