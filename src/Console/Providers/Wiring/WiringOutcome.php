<?php

namespace Lomkit\Osdd\Console\Providers\Wiring;

use Illuminate\Console\OutputStyle;

interface WiringOutcome
{
    public function report(OutputStyle $output): void;
}
