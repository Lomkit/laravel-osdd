<?php

namespace Lomkit\Osdd\Tests;

use Laravel\Prompts\Prompt;

abstract class PromptState extends Prompt
{
    public static function flush(): void
    {
        static::$cancelUsing = null;
        static::$validateUsing = null;
        static::$revertUsing = null;
        static::$shouldFallback = false;
        static::$fallbacks = [];
    }
}
