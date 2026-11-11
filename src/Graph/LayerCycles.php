<?php

namespace Lomkit\Osdd\Graph;

final class LayerCycles
{
    private int $nextIndex = 0;

    /**
     * @var array<string, int>
     */
    private array $index = [];

    /**
     * @var array<string, int>
     */
    private array $lowLink = [];

    /**
     * @var list<string>
     */
    private array $stack = [];

    /**
     * @var list<list<string>>
     */
    private array $components = [];

    private function __construct(private readonly LayerGraph $graph) {}

    /**
     * One shortest cycle per group of mutually requiring layers, starting and ending with the same layer.
     *
     * @return list<list<string>>
     */
    public static function in(LayerGraph $graph): array
    {
        $cycles = new self($graph);

        foreach ($graph->names() as $layer) {
            if (! isset($cycles->index[$layer])) {
                $cycles->connect($layer);
            }
        }

        $found = array_values(array_filter(array_map($cycles->cycleThrough(...), $cycles->components)));
        usort($found, fn (array $a, array $b): int => $a <=> $b);

        return $found;
    }

    private function connect(string $layer): void
    {
        $this->index[$layer] = $this->lowLink[$layer] = $this->nextIndex++;
        $this->stack[] = $layer;

        foreach ($this->graph->requires($layer) as $required) {
            if (! isset($this->index[$required])) {
                $this->connect($required);
                $this->lowLink[$layer] = min($this->lowLink[$layer], $this->lowLink[$required]);
            } elseif (in_array($required, $this->stack, true)) {
                $this->lowLink[$layer] = min($this->lowLink[$layer], $this->index[$required]);
            }
        }

        if ($this->lowLink[$layer] === $this->index[$layer]) {
            $this->components[] = $this->popComponent($layer);
        }
    }

    /**
     * @return list<string>
     */
    private function popComponent(string $root): array
    {
        $component = [];

        do {
            $layer = (string) array_pop($this->stack);
            $component[] = $layer;
        } while ($layer !== $root);

        sort($component);

        return $component;
    }

    /**
     * @param  list<string>  $component
     * @return list<string>
     */
    private function cycleThrough(array $component): array
    {
        $start = $component[0];
        $paths = [[$start]];
        $visited = [$start];

        while ($paths !== []) {
            $path = array_shift($paths);

            foreach ($this->graph->requires($path[count($path) - 1]) as $next) {
                if ($next === $start) {
                    return [...$path, $start];
                }

                if (in_array($next, $component, true) && ! in_array($next, $visited, true)) {
                    $visited[] = $next;
                    $paths[] = [...$path, $next];
                }
            }
        }

        return [];
    }
}
