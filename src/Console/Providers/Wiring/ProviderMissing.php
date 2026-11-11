<?php

namespace Lomkit\Osdd\Console\Providers\Wiring;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerName;

final readonly class ProviderMissing implements WiringOutcome
{
    public function __construct(
        public LayerName $layer,
        public ProviderMethod $method,
        public string $line,
    ) {}

    #[\Override]
    public function report(OutputStyle $output): void
    {
        (new Factory($output))->warn("Layer [{$this->layer->toString()}] has no service provider. Add this line to the {$this->method->value}() method of its provider:");
        $output->writeln($this->line);
    }
}
