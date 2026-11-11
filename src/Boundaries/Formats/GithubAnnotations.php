<?php

namespace Lomkit\Osdd\Boundaries\Formats;

use Illuminate\Console\OutputStyle;
use Lomkit\Osdd\Boundaries\BoundaryReport;
use Symfony\Component\Console\Output\OutputInterface;

final class GithubAnnotations implements BoundaryReportFormat
{
    #[\Override]
    public function render(BoundaryReport $report, OutputStyle $output): void
    {
        foreach ($report->undeclared as $dependency) {
            $this->annotate($output, 'error', [
                'file' => $dependency->reference->file,
                'line' => (string) $dependency->reference->line,
                'title' => 'Undeclared layer dependency',
            ], ViolationMessages::undeclared($dependency));
        }

        foreach ($report->cycles as $cycle) {
            $this->annotate($output, 'error', ['title' => 'Layer cycle'], ViolationMessages::cycle($cycle));
        }

        foreach ($report->unused as $requirement) {
            $this->annotate($output, 'warning', [
                'file' => $requirement->composerFile,
                'title' => 'Unused layer requirement',
            ], ViolationMessages::unused($requirement));
        }

        $output->writeln(ViolationMessages::summary($report), OutputInterface::OUTPUT_RAW);
    }

    /**
     * @param  array<string, string>  $properties
     */
    private function annotate(OutputStyle $output, string $level, array $properties, string $message): void
    {
        $pairs = array_map(
            fn (string $key, string $value): string => $key.'='.strtr($value, ['%' => '%25', "\r" => '%0D', "\n" => '%0A', ':' => '%3A', ',' => '%2C']),
            array_keys($properties),
            $properties,
        );

        $output->writeln(
            "::{$level} ".implode(',', $pairs).'::'.strtr($message, ['%' => '%25', "\r" => '%0D', "\n" => '%0A']),
            OutputInterface::OUTPUT_RAW,
        );
    }
}
