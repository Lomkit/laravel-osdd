<?php

namespace Lomkit\Osdd\Boundaries\Formats;

use Illuminate\Console\OutputStyle;
use Lomkit\Osdd\Boundaries\BoundaryReport;

interface BoundaryReportFormat
{
    public function render(BoundaryReport $report, OutputStyle $output): void;
}
