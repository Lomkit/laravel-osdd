<?php

namespace Lomkit\Osdd\Console\Providers\Source;

use PhpToken;

final readonly class DeclaredParent
{
    private const array NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

    /**
     * The fully qualified parent, without leading backslash, of the class the code declares under this short name;
     * null when the code declares no such class or the class extends nothing.
     */
    public static function of(string $code, string $shortClass): ?string
    {
        $tokens = array_values(array_filter(PhpToken::tokenize($code), fn (PhpToken $token): bool => ! $token->isIgnorable()));

        foreach ($tokens as $index => $token) {
            $declaresClass = $token->is(T_CLASS)
                && ($tokens[$index + 1] ?? null)?->is(T_STRING) === true
                && strcasecmp($tokens[$index + 1]->text, $shortClass) === 0;

            if ($declaresClass && ($tokens[$index + 2] ?? null)?->is(T_EXTENDS) === true && ($tokens[$index + 3] ?? null)?->is(self::NAME_TOKENS) === true) {
                return ltrim(ProviderImports::fromTokens($tokens)->resolve($tokens[$index + 3]->text), '\\');
            }
        }

        return null;
    }
}
