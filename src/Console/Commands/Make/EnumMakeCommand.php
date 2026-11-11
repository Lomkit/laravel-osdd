<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:enum')]
final class EnumMakeCommand extends \Illuminate\Foundation\Console\EnumMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Enums';
    }
}
