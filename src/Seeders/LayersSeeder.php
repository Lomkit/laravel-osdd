<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;
use Lomkit\Osdd\SeederRegistry;

class LayersSeeder extends Seeder
{
    public function run(SeederRegistry $registry): void
    {
        $seeders = $registry->seeders();

        if ($seeders === []) {
            $this->command?->outputComponents()->warn('No OSDD seeders registered. Make sure your layer service providers call loadSeeders().');

            return;
        }

        foreach ($seeders as $seeder) {
            if (! class_exists($seeder)) {
                $this->command?->outputComponents()->warn("Seeder [{$seeder}] not found, skipping.");

                continue;
            }

            $this->call($seeder);
        }
    }
}
