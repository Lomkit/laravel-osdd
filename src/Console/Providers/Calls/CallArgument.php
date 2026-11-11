<?php

namespace Lomkit\Osdd\Console\Providers\Calls;

interface CallArgument
{
    public function render(ProviderFile $file): string;
}
