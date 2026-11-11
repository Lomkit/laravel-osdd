<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Str;

final readonly class GeneratorCase
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $command,
        public string $name,
        public string $file,
        public string $class,
        public array $options = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function arguments(): array
    {
        return ['name' => $this->name, ...$this->options];
    }

    public function prefixed(string $prefix): self
    {
        return new self(
            $this->command,
            $this->prefixBasename($this->name, '/', $prefix),
            $this->prefixBasename($this->file, '/', $prefix),
            $this->prefixBasename($this->class, '\\', $prefix),
            $this->options,
        );
    }

    private function prefixBasename(string $path, string $separator, string $prefix): string
    {
        return Str::contains($path, $separator)
            ? Str::beforeLast($path, $separator).$separator.$prefix.Str::afterLast($path, $separator)
            : $prefix.$path;
    }
}
