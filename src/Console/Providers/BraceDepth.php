<?php

namespace Lomkit\Osdd\Console\Providers;

use PhpToken;

final class BraceDepth
{
    public static function delta(PhpToken $token): int
    {
        if ($token->is(['{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
            return 1;
        }

        return $token->is('}') ? -1 : 0;
    }
}
