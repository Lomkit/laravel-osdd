<?php

namespace Lomkit\Osdd\Boundaries\Php;

use PhpToken;

final class Tokens
{
    /**
     * @param  list<PhpToken>  $tokens
     */
    private function __construct(private readonly array $tokens) {}

    public static function significantIn(string $code): self
    {
        return new self(array_values(array_filter(
            PhpToken::tokenize($code),
            fn (PhpToken $token): bool => ! $token->isIgnorable(),
        )));
    }

    public function count(): int
    {
        return count($this->tokens);
    }

    public function at(int $index): ?PhpToken
    {
        return $this->tokens[$index] ?? null;
    }

    /**
     * @param  int|string|array<int|string>  $kind
     */
    public function is(int $index, int|string|array $kind): bool
    {
        return $this->at($index)?->is($kind) ?? false;
    }

    public function isName(int $index): bool
    {
        return $this->is($index, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED]);
    }
}
