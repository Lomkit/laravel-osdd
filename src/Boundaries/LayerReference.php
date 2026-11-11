<?php

namespace Lomkit\Osdd\Boundaries;

final readonly class LayerReference
{
    public function __construct(
        public string $layer,
        public string $class,
        public string $file,
        public int $line,
        public bool $fromTests,
    ) {}
}
