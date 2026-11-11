<?php

namespace Lomkit\Osdd\Livewire;

use Illuminate\Contracts\Container\Container;
use Livewire\Finder\Finder;
use Livewire\LivewireManager;

final class InstalledLivewire
{
    public static function runsIn(Container $container): bool
    {
        return class_exists(Finder::class) && $container->bound(LivewireManager::class);
    }
}
