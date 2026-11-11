<?php

namespace Lomkit\Osdd\Boundaries;

final readonly class BoundaryReport
{
    /**
     * @param  list<string>  $layers
     * @param  list<UndeclaredDependency>  $undeclared
     * @param  list<UnusedRequirement>  $unused
     * @param  list<list<string>>  $cycles
     */
    public function __construct(
        public array $layers,
        public bool $strict,
        public array $undeclared,
        public array $unused,
        public array $cycles,
    ) {}

    public function violationCount(): int
    {
        return count($this->undeclared) + count($this->cycles);
    }

    public function hasViolations(): bool
    {
        return $this->violationCount() > 0;
    }

    /**
     * @return array{layers: list<string>, strict: bool, undeclared: list<array<string, int|string>>, unused: list<array<string, string>>, cycles: list<list<string>>}
     */
    public function toArray(): array
    {
        return [
            'layers' => $this->layers,
            'strict' => $this->strict,
            'undeclared' => array_map(fn (UndeclaredDependency $dependency): array => $dependency->toArray(), $this->undeclared),
            'unused' => array_map(fn (UnusedRequirement $requirement): array => $requirement->toArray(), $this->unused),
            'cycles' => $this->cycles,
        ];
    }
}
