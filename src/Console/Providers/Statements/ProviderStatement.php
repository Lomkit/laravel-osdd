<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\ProviderMethod;

interface ProviderStatement
{
    public function method(): ProviderMethod;

    public function call(): ProviderCall;

    /**
     * What the statement registers, as reported once it is added.
     */
    public function label(): string;
}
