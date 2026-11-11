<?php

namespace Lomkit\Osdd\Events;

use ReflectionException;

final class ListenerClassNotFoundException extends ReflectionException
{
    public function __construct(public readonly string $listenerFile, public readonly string $class)
    {
        parent::__construct("The listener file [{$listenerFile}] does not declare the class [{$class}].");
    }
}
