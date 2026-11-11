<?php

namespace Lomkit\Osdd\Console\Providers\Source;

use Lomkit\Osdd\Console\Providers\BraceDepth;
use PhpToken;

final readonly class ProviderImports
{
    private const array NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

    /**
     * @param  array<string, string>  $classes  alias => fully qualified class name, without leading backslash
     */
    private function __construct(
        private string $namespace,
        private array $classes,
    ) {}

    public static function none(): self
    {
        return new self('', []);
    }

    /**
     * The namespace and the class imports declared outside any brace.
     *
     * @param  list<PhpToken>  $tokens  Tokens without whitespace and comments
     */
    public static function fromTokens(array $tokens): self
    {
        $namespace = '';
        $classes = [];
        $depth = 0;

        for ($index = 0; $index < count($tokens); $index++) {
            $token = $tokens[$index];
            $depth += BraceDepth::delta($token);

            if ($depth === 0 && $token->is(T_NAMESPACE) && ($tokens[$index + 1] ?? null)?->is(self::NAME_TOKENS)) {
                $namespace = $tokens[$index + 1]->text;
            }

            if ($depth === 0 && $token->is(T_USE)) {
                $index = self::readUse($tokens, $index + 1, $classes);
            }
        }

        return new self(trim($namespace, '\\'), $classes);
    }

    public function aliasOf(string $class): ?string
    {
        $alias = array_search(ltrim($class, '\\'), $this->classes, true);

        return $alias === false ? null : $alias;
    }

    /**
     * The fully qualified form, with a leading backslash, of a class name written in this file.
     */
    public function resolve(string $name): string
    {
        if (str_starts_with($name, '\\')) {
            return $name;
        }

        $segments = explode('\\', $name);

        if (isset($this->classes[$segments[0]])) {
            return '\\'.implode('\\', [$this->classes[$segments[0]], ...array_slice($segments, 1)]);
        }

        return '\\'.ltrim($this->namespace.'\\'.$name, '\\');
    }

    /**
     * @param  list<PhpToken>  $tokens
     * @param  array<string, string>  $classes
     */
    private static function readUse(array $tokens, int $index, array &$classes): int
    {
        $importsClasses = ! ($tokens[$index] ?? null)?->is([T_FUNCTION, T_CONST]);
        [$prefix, $name, $alias] = ['', '', null];

        for (; isset($tokens[$index]) && ! $tokens[$index]->is(';'); $index++) {
            $token = $tokens[$index];

            if ($token->is(T_AS)) {
                $alias = ($tokens[++$index] ?? null)?->text;
            } elseif ($token->is('{')) {
                [$prefix, $name] = [$name, ''];
            } elseif ($token->is([',', '}'])) {
                self::add($classes, $importsClasses, $prefix.$name, $alias);
                [$name, $alias] = ['', null];
            } else {
                $name .= $token->text;
            }
        }

        self::add($classes, $importsClasses, $prefix.$name, $alias);

        return $index;
    }

    /**
     * @param  array<string, string>  $classes
     */
    private static function add(array &$classes, bool $importsClasses, string $name, ?string $alias): void
    {
        $segments = explode('\\', trim($name, '\\'));
        $short = end($segments);

        if ($importsClasses && $short !== '' && ! str_ends_with($name, '\\')) {
            $classes[$alias ?? $short] = implode('\\', $segments);
        }
    }
}
