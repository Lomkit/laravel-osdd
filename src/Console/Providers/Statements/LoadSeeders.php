<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\ClassListArgument;
use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\ProviderMethod;

final readonly class LoadSeeders implements ProviderStatement
{
    public function __construct(private string $seeder) {}

    #[\Override]
    public function method(): ProviderMethod
    {
        return ProviderMethod::Boot;
    }

    #[\Override]
    public function call(): ProviderCall
    {
        return new ProviderCall('loadSeeders', PresenceRule::SameCall, new ClassListArgument($this->seeder));
    }

    #[\Override]
    public function label(): string
    {
        return ltrim($this->seeder, '\\');
    }
}
