<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;

/**
 * @phpstan-require-extends Seeder
 */
trait CallsRegisteredSeeders
{
    /**
     * @param  list<class-string<Seeder>>  $seeders
     *
     * @throws SeederNotFoundException
     */
    private function callRegistered(array $seeders, string $nothingRegisteredWarning): void
    {
        if ($seeders === []) {
            $this->command?->outputComponents()->warn($nothingRegisteredWarning);

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
