<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;

interface SeederSelection
{
    /**
     * @param  list<class-string<Seeder>>  $seeders
     * @return list<class-string<Seeder>>
     */
    public function select(array $seeders): array;

    public function nothingSelectedWarning(): string;
}
