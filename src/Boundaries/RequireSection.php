<?php

namespace Lomkit\Osdd\Boundaries;

enum RequireSection: string
{
    case Require = 'require';
    case RequireDev = 'require-dev';
}
