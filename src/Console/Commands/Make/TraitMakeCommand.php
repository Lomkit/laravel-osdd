<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:trait')]
final class TraitMakeCommand extends \Illuminate\Foundation\Console\TraitMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Concerns';
    }
}
