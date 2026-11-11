<?php

namespace Lomkit\Osdd\Events;

use Illuminate\Support\Str;

final readonly class ListenerReference
{
    private const string DEFAULT_METHOD = 'handle';

    private const string INVOKABLE_METHOD = '__invoke';

    private function __construct(
        private string $class,
        private string $method,
    ) {}

    /**
     * The class and method the dispatcher calls for a listener given as "Class", "Class@method" or [Class, method];
     * null for closures and objects.
     */
    public static function of(mixed $listener): ?self
    {
        if (is_string($listener)) {
            [$class, $method] = Str::parseCallback($listener, self::DEFAULT_METHOD);

            return self::called((string) $class, (string) $method);
        }

        if (is_array($listener) && is_string($listener[0] ?? null) && is_string($listener[1] ?? null)) {
            return self::called($listener[0], $listener[1]);
        }

        return null;
    }

    public function isSameAs(self $other): bool
    {
        return strcasecmp($this->class, $other->class) === 0 && strcasecmp($this->method, $other->method) === 0;
    }

    private static function called(string $class, string $method): self
    {
        $class = ltrim($class, '\\');

        return new self($class, method_exists($class, $method) ? $method : self::INVOKABLE_METHOD);
    }
}
