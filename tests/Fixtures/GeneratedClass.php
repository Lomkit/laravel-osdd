<?php

namespace Lomkit\Osdd\Tests\Fixtures;

use PHPUnit\Framework\Assert;
use ReflectionClass;

final class GeneratedClass
{
    /**
     * Require a generated file and reflect the class it declares.
     *
     * @return ReflectionClass<object>
     */
    public static function load(string $path, string $class): ReflectionClass
    {
        Assert::assertFileExists($path);
        Assert::assertFalse(
            class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false) || enum_exists($class, false),
            "[{$class}] is already declared by another test: give the generated class a name no other test loads.",
        );

        require $path;

        Assert::assertTrue(
            class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false) || enum_exists($class, false),
            "[{$path}] does not declare [{$class}].",
        );

        return new ReflectionClass($class);
    }
}
