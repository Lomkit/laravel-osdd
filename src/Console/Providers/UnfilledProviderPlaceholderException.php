<?php

namespace Lomkit\Osdd\Console\Providers;

use LogicException;

final class UnfilledProviderPlaceholderException extends LogicException
{
    public function __construct(public readonly string $placeholder)
    {
        parent::__construct("No statement fills the [{$placeholder}] placeholder of the provider stub.");
    }
}
