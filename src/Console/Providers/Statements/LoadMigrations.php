<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\LayerPathArgument;
use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerFolder;

final readonly class LoadMigrations implements ProviderStatement
{
    #[\Override]
    public function method(): ProviderMethod
    {
        return ProviderMethod::Boot;
    }

    #[\Override]
    public function call(): ProviderCall
    {
        return new ProviderCall('loadMigrationsFrom', PresenceRule::SameCall, LayerPathArgument::folder(LayerFolder::Migrations));
    }

    #[\Override]
    public function label(): string
    {
        return LayerFolder::Migrations->value;
    }
}
