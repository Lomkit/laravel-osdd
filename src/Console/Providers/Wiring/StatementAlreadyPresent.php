<?php

namespace Lomkit\Osdd\Console\Providers\Wiring;

use Illuminate\Console\OutputStyle;

final readonly class StatementAlreadyPresent implements WiringOutcome
{
    #[\Override]
    public function report(OutputStyle $output): void {}
}
