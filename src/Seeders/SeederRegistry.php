<?php

namespace Lomkit\Osdd\Seeders;

use Illuminate\Database\Seeder;

class SeederRegistry
{
    /**
     * @var list<array{priority: int, class: class-string<Seeder>}>
     */
    private array $seeders = [];

    /**
     * A seeder pushed twice keeps its first registration and priority.
     *
     * @param  int  $priority  Lower values run first; ties preserve registration order
     * @param  class-string<Seeder>  ...$seeders
     */
    public function push(int $priority, string ...$seeders): void
    {
        foreach ($seeders as $seeder) {
            if ($this->has($seeder)) {
                continue;
            }

            $this->seeders[] = ['priority' => $priority, 'class' => $seeder];
        }
    }

    private function has(string $seeder): bool
    {
        $seeder = ltrim($seeder, '\\');

        foreach ($this->seeders as $registered) {
            if (strcasecmp(ltrim($registered['class'], '\\'), $seeder) === 0) {
                return true;
            }
        }

        return false;
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
