<?php

namespace Lomkit\Osdd\Generation;

final readonly class ClassLocation
{
    public function __construct(
        public string $namespace,
        public string $directory,
    ) {}

    public function fileOf(string $class): string
    {
        $relative = str_starts_with($class, $this->namespace) ? substr($class, strlen($this->namespace)) : $class;

        return $this->directory.'/'.str_replace('\\', '/', $relative).'.php';
    }
}
