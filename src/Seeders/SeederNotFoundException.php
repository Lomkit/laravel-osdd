<?php

namespace Lomkit\Osdd\Seeders;

use RuntimeException;

class SeederNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $seeder)
    {
        parent::__construct("Seeder [{$seeder}] is registered by a layer but does not exist. Fix the loadSeeders() call of its layer service provider.");
    }
}
