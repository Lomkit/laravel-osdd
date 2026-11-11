<?php

namespace Lomkit\Osdd\Boundaries\Php;

final readonly class ClassReference
{
    public function __construct(
        public string $name,
        public int $line,
    ) {}
}
