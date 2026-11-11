<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;

class TenantLayersSeeder extends Seeder
{
    use CallsRegisteredSeeders;

    public function run(TenantSeederRegistry $registry): void
    {
        $this->callRegistered(
            $registry->seeders(),
            'No OSDD tenant seeders registered. Make sure your layer service providers call loadTenantSeeders().',
        );
    }
}
