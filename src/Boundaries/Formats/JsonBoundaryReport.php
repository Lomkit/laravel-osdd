<?php

namespace Lomkit\Osdd\Boundaries\Formats;

use Illuminate\Console\OutputStyle;
use Lomkit\Osdd\Boundaries\BoundaryReport;
use Symfony\Component\Console\Output\OutputInterface;

final class JsonBoundaryReport implements BoundaryReportFormat
{
    #[\Override]
    public function render(BoundaryReport $report, OutputStyle $output): void
    {
        $output->writeln(
            json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            OutputInterface::OUTPUT_RAW,
        );
    }
}
