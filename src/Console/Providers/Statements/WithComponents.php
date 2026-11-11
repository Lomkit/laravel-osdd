<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\Calls\StringArgument;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderMethod;

final readonly class WithComponents implements ProviderStatement
{
    private string $componentNamespace;

    public function __construct(string $componentNamespace, private LayerResourceNamespace $namespace)
    {
        $this->componentNamespace = trim($componentNamespace, '\\');
    }

    #[\Override]
    public function method(): ProviderMethod
    {
        return ProviderMethod::Boot;
    }

    #[\Override]
    public function call(): ProviderCall
    {
        return new ProviderCall('withComponents', PresenceRule::SameHelper, new StringArgument($this->componentNamespace), ...$this->namespace->arguments());
    }

    #[\Override]
    public function label(): string
    {
        return $this->componentNamespace;
    }
}
