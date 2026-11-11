<?php

namespace Lomkit\Osdd\Layers;

use InvalidArgumentException;

class InvalidLayerNameException extends InvalidArgumentException
{
    public static function notAComposerName(string $name): self
    {
        return new self("Invalid layer name [{$name}]. Use a Composer package name: vendor/package, in lowercase letters and digits, words separated by '-', '_' or '.' (functional/orders).");
    }

    public static function notANamespace(string $name, string $namespace): self
    {
        return new self("Invalid layer name [{$name}]: its namespace [{$namespace}] is not a valid PHP namespace. Start each part of the name with a letter.");
    }
}
