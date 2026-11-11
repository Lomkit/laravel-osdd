<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:scope')]
final class ScopeMakeCommand extends \Illuminate\Foundation\Console\ScopeMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Models\\Scopes';
    }
}
