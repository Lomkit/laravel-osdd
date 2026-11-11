<?php

namespace Lomkit\Osdd\Console\Providers;

use RuntimeException;

class ProviderMethodNotFoundException extends RuntimeException
{
    public function __construct(public readonly ProviderMethod $method)
    {
        parent::__construct("No {$method->value}() method with a body was found.");
    }
}
