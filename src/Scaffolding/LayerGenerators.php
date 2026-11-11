<?php

namespace Lomkit\Osdd\Scaffolding;

use Lomkit\Osdd\Layers\LayerName;
use Symfony\Component\Console\Command\Command;

final readonly class LayerGenerators
{
    public function __construct(
        private ScaffoldConsole $console,
        private LayerName $layer,
        private bool $force,
    ) {}

    /**
     * Run an osdd generator in the layer being scaffolded.
     *
     * @param  class-string<Command>  $generator
     * @param  array<string, string|bool|null>  $arguments
     */
    public function run(string $generator, array $arguments): void
    {
        $this->console->call($generator, [...$arguments, '--layer' => $this->layer->toString()], $this->force);
    }
}
