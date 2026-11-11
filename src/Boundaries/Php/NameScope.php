<?php

namespace Lomkit\Osdd\Boundaries\Php;

final class NameScope
{
    /**
     * @var array<string, string>
     */
    private array $classAliases = [];

    private function __construct(private readonly string $namespace) {}

    public static function global(): self
    {
        return new self('');
    }

    public static function of(string $namespace): self
    {
        return new self(trim($namespace, '\\'));
    }

    public function import(Import $import): void
    {
        if ($import->kind === ImportKind::ClassLike) {
            $this->classAliases[strtolower($import->alias)] = $import->name;
        }
    }

    public function resolveQualified(string $name): string
    {
        [$first, $rest] = explode('\\', $name, 2);

        return ($this->classAliases[strtolower($first)] ?? $this->prefixed($first)).'\\'.$rest;
    }

    public function resolveRelative(string $name): string
    {
        return $this->prefixed(substr($name, strlen('namespace\\')));
    }

    private function prefixed(string $name): string
    {
        return $this->namespace === '' ? $name : $this->namespace.'\\'.$name;
    }
}
