<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\LayerPathArgument;
use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerFolder;

final readonly class WithTranslations implements ProviderStatement
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
        return new ProviderCall('withTranslations', PresenceRule::SameHelper, LayerPathArgument::folder(LayerFolder::Lang), ...$this->namespace->arguments());
    }

    #[\Override]
    public function label(): string
    {
        return LayerFolder::Lang->value;
    }
}
