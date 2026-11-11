<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderMethod;

final readonly class WithLivewire implements ProviderStatement
{
    public function __construct(private LayerResourceNamespace $namespace) {}

    #[\Override]
    public function method(): ProviderMethod
    {
        return ProviderMethod::Boot;
    }

    #[\Override]
    public function call(): ProviderCall
    {
        return new ProviderCall('withLivewire', PresenceRule::SameHelper, ...$this->namespace->arguments());
    }

    #[\Override]
    public function label(): string
    {
        return 'livewire:'.$this->namespace->name();
    }
}
