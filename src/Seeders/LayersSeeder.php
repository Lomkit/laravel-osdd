<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;

class LayersSeeder extends Seeder
{
    /**
     * @throws SeederNotFoundException
     */
    public function run(SeederRegistry $registry, SeederSelection $selection): void
    {
        $seeders = $selection->select($registry->seeders());

        if ($seeders === []) {
            $this->command?->outputComponents()->warn($selection->nothingSelectedWarning());

            return;
        }

        foreach ($seeders as $seeder) {
            if (! class_exists($seeder)) {
                throw new SeederNotFoundException($seeder);
            }
        }

        $this->call($seeders);
    }
}
