<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:interface')]
final class InterfaceMakeCommand extends \Illuminate\Foundation\Console\InterfaceMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Contracts';
    }
}
