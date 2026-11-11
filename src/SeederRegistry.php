<?php

namespace Lomkit\Osdd;

use Illuminate\Database\Seeder;

class SeederRegistry
{
    /**
     * @var list<array{priority: int, class: class-string<Seeder>}>
     */
    private array $seeders = [];

    /**
     * @param  int  $priority  Lower values run first; ties preserve registration order
     * @param  class-string<Seeder>  ...$seeders
     */
    public function push(int $priority, string ...$seeders): void
    {
        foreach ($seeders as $seeder) {
            $this->seeders[] = ['priority' => $priority, 'class' => $seeder];
        }
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public function seeders(): array
    {
        $sorted = $this->seeders;
        usort($sorted, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return array_column($sorted, 'class');
    }
}
