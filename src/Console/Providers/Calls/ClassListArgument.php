<?php

namespace Lomkit\Osdd\Console\Providers\Calls;

final readonly class ClassListArgument implements CallArgument
{
    /**
     * @var list<string>
     */
    private array $classes;

    public function __construct(string ...$classes)
    {
        $this->classes = array_values(array_map(fn (string $class): string => ltrim($class, '\\'), $classes));
    }

    #[\Override]
    public function render(ProviderFile $file): string
    {
        return '['.implode(', ', array_map(fn (string $class): string => $file->classReference($class).'::class', $this->classes)).']';
    }
}
