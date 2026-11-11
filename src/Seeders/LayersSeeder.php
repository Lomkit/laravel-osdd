<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;
use Lomkit\Osdd\SeederRegistry;

class LayersSeeder extends Seeder
{
    use CallsRegisteredSeeders;

    public function run(SeederRegistry $registry): void
    {
        $this->callRegistered(
            $registry->seeders(),
            'No OSDD seeders registered. Make sure your layer service providers call loadSeeders().',
        );
    }
}
