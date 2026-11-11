<?php

namespace Lomkit\Osdd\Boundaries\Formats;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Lomkit\Osdd\Boundaries\BoundaryReport;
use Lomkit\Osdd\Boundaries\UndeclaredDependency;

final class TextBoundaryReport implements BoundaryReportFormat
{
    #[\Override]
    public function render(BoundaryReport $report, OutputStyle $output): void
    {
        $components = new Factory($output);

        foreach ($this->byLayerPair($report->undeclared) as $dependencies) {
            $components->error(ViolationMessages::undeclaredLayer($dependencies[0]->layer, $dependencies[0]->reference->layer));

            foreach ($dependencies as $dependency) {
                $components->twoColumnDetail("{$dependency->reference->file}:{$dependency->reference->line}", $dependency->reference->class);
            }

            $output->newLine();
        }

        foreach ($report->cycles as $cycle) {
            $components->error(ViolationMessages::cycle($cycle));
        }

        foreach ($report->unused as $requirement) {
            $components->warn(ViolationMessages::unused($requirement));
        }

        $report->hasViolations()
            ? $components->error(ViolationMessages::summary($report))
            : $components->info(ViolationMessages::summary($report));
    }

    /**
     * @param  list<UndeclaredDependency>  $undeclared
     * @return array<string, non-empty-list<UndeclaredDependency>>
     */
    private function byLayerPair(array $undeclared): array
    {
        $groups = [];

        foreach ($undeclared as $dependency) {
            $groups[$dependency->layer.' '.$dependency->reference->layer][] = $dependency;
        }

        return $groups;
    }
}
