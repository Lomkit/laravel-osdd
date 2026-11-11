<?php

namespace Lomkit\Osdd\Console\Providers\Calls;

final readonly class StringArgument implements CallArgument
{
    public function __construct(private string $value) {}

    #[\Override]
    public function render(ProviderFile $file): string
    {
        return var_export($this->value, true);
    }
}
