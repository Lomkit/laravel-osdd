<?php

namespace Lomkit\Osdd\Boundaries\Php;

enum ImportKind
{
    case ClassLike;
    case Function;
    case Constant;

    public static function fromToken(?\PhpToken $token): ?self
    {
        return match (true) {
            $token?->is(T_FUNCTION) === true => self::Function,
            $token?->is(T_CONST) === true => self::Constant,
            default => null,
        };
    }
}
