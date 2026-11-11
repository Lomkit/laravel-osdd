<?php

namespace Lomkit\Osdd\Console\Providers;

enum ProviderMethod: string
{
    case Boot = 'boot';
    case Register = 'register';
}
