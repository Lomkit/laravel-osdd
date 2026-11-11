<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:policy')]
final class PolicyMakeCommand extends \Illuminate\Foundation\Console\PolicyMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    protected function getStub(): string
    {
        if ($this->option('model')) {
            return parent::getStub();
        }

        return __DIR__.'/../../../Stubs/make/policy.stub';
    }
}
