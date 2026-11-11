<?php

namespace Lomkit\Osdd\Boundaries\Formats;

use Illuminate\Support\Str;
use Lomkit\Osdd\Boundaries\BoundaryReport;
use Lomkit\Osdd\Boundaries\RequireSection;
use Lomkit\Osdd\Boundaries\UndeclaredDependency;
use Lomkit\Osdd\Boundaries\UnusedRequirement;

final class ViolationMessages
{
    public static function undeclared(UndeclaredDependency $dependency): string
    {
        return "{$dependency->layer} uses {$dependency->reference->class} from {$dependency->reference->layer} but does not require {$dependency->reference->layer}.";
    }

    public static function undeclaredLayer(string $layer, string $target): string
    {
        return "{$layer} uses {$target} but does not require it.";
    }

    /**
     * @param  list<string>  $cycle
     */
    public static function cycle(array $cycle): string
    {
        return 'Cycle in the layer requirements: '.implode(' -> ', $cycle).'.';
    }

    public static function unused(UnusedRequirement $requirement): string
    {
        $section = $requirement->section === RequireSection::RequireDev ? ' in require-dev' : '';

        return "{$requirement->layer} requires {$requirement->requirement}{$section} but does not use it.";
    }

    public static function summary(BoundaryReport $report): string
    {
        $layers = count($report->layers).' '.Str::plural('layer', count($report->layers));
        $warnings = count($report->unused) === 0 ? '' : ' ('.count($report->unused).' unused '.Str::plural('requirement', count($report->unused)).')';

        if (! $report->hasViolations()) {
            return "No boundary violation in {$layers}{$warnings}.";
        }

        return $report->violationCount().' boundary '.Str::plural('violation', $report->violationCount())." in {$layers}{$warnings}.";
    }
}
