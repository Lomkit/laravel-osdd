<?php

namespace Lomkit\Osdd\Tenancy;

use Illuminate\Database\Seeder;
use Lomkit\Osdd\Seeders\PrioritizedSeeders;

final class TenantSeederRegistry
{
    private readonly PrioritizedSeeders $seeders;

    public function __construct()
    {
        $this->seeders = new PrioritizedSeeders;
    }

    /**
     * A seeder pushed twice keeps its first registration and priority.
     *
     * @param  int  $priority  Lower values run first; ties preserve registration order
     * @param  class-string<Seeder>  ...$seeders
     */
    public function push(int $priority, string ...$seeders): void
    {
        $this->seeders->push($priority, ...$seeders);
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public function seeders(): array
    {
        return $this->seeders->inPriorityOrder();
    }
}
