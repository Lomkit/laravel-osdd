<?php

namespace Lomkit\Osdd\Console\Providers\Wiring;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Lomkit\Osdd\Console\Providers\ProviderMethod;

final readonly class ProviderMethodMissing implements WiringOutcome
{
    public function __construct(
        public string $providerPath,
        public ProviderMethod $method,
        public string $line,
    ) {}

    #[\Override]
    public function report(OutputStyle $output): void
    {
        (new Factory($output))->warn("No {$this->method->value}() method found in [{$this->providerPath}]. Add this line to it:");
        $output->writeln($this->line);
    }
}
