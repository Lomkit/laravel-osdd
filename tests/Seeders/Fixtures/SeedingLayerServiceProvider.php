<?php

namespace Lomkit\Osdd\Tests\Seeders\Fixtures;

use Lomkit\Osdd\LayerServiceProvider;

class SeedingLayerServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadSeeders([OrdersLayerSeeder::class], 10);
        $this->loadSeeders([UsersLayerSeeder::class]);
    }
}
