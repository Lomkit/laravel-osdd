<?php

namespace Lomkit\Osdd\Boundaries\Php;

final readonly class Import
{
    public function __construct(
        public ImportKind $kind,
        public string $name,
        public string $alias,
        public int $line,
    ) {}
}
