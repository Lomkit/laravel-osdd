<?php

namespace Lomkit\Osdd\Console\Providers\Wiring;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;

final readonly class StatementAdded implements WiringOutcome
{
    public function __construct(
        public string $label,
        public string $providerPath,
    ) {}

    #[\Override]
    public function report(OutputStyle $output): void
    {
        (new Factory($output))->info("Registered [{$this->label}] in [{$this->providerPath}].");
    }
}
