<?php

namespace Lomkit\Osdd\Boundaries\Php;

use PhpToken;

final class ReferencedNames
{
    private const array OPENING_BRACES = ['{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES];

    private NameScope $scope;

    private int $depth = 0;

    private int $importDepth = 0;

    /**
     * @var list<ClassReference>
     */
    private array $references = [];

    private function __construct(private readonly Tokens $tokens)
    {
        $this->scope = NameScope::global();
    }

    /**
     * Imports, qualified and fully qualified names; unqualified names only count through their import.
     *
     * @return list<ClassReference>
     */
    public static function inCode(string $code): array
    {
        $scanner = new self(Tokens::significantIn($code));
        $scanner->scan();

        return $scanner->references;
    }

    /**
     * @return list<ClassReference>
     */
    public static function inFile(string $path): array
    {
        return self::inCode((string) file_get_contents($path));
    }

    private function scan(): void
    {
        for ($index = 0; $index < $this->tokens->count(); $index++) {
            $token = $this->tokens->at($index);

            if ($token !== null) {
                $index = $this->visit($token, $index);
            }
        }
    }

    private function visit(PhpToken $token, int $index): int
    {
        if ($token->is(self::OPENING_BRACES)) {
            $this->depth++;
        } elseif ($token->is('}')) {
            $this->depth--;
        } elseif ($token->is(T_NAMESPACE)) {
            return $this->enterNamespace($index);
        } elseif ($token->is(T_USE) && $this->isImport($index)) {
            return $this->import($index);
        } elseif (($name = $this->resolve($token)) !== null) {
            $this->references[] = new ClassReference($name, $token->line);
        }

        return $index;
    }

    private function resolve(PhpToken $token): ?string
    {
        return match (true) {
            $token->is(T_NAME_FULLY_QUALIFIED) => ltrim($token->text, '\\'),
            $token->is(T_NAME_QUALIFIED) => $this->scope->resolveQualified($token->text),
            $token->is(T_NAME_RELATIVE) => $this->scope->resolveRelative($token->text),
            default => null,
        };
    }

    private function enterNamespace(int $index): int
    {
        $named = $this->tokens->isName($index + 1);
        $this->scope = $named ? NameScope::of((string) $this->tokens->at($index + 1)?->text) : NameScope::global();
        $next = $named ? $index + 2 : $index + 1;
        $this->importDepth = $this->tokens->is($next, '{') ? $this->depth + 1 : $this->depth;

        return $named ? $index + 1 : $index;
    }

    private function isImport(int $index): bool
    {
        return $this->depth === $this->importDepth && ! $this->tokens->is($index - 1, ')') && ! $this->tokens->is($index + 1, '(');
    }

    private function import(int $index): int
    {
        [$imports, $end] = UseStatement::parse($this->tokens, $index);

        foreach ($imports as $import) {
            $this->scope->import($import);
            $this->references[] = new ClassReference($import->name, $import->line);
        }

        return $end;
    }
}
