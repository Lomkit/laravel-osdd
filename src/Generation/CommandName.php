<?php

namespace Lomkit\Osdd\Generation;

use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

final class CommandName
{
    /**
     * @param  class-string  $command
     */
    public static function of(string $command): string
    {
        $attributes = (new ReflectionClass($command))->getAttributes(AsCommand::class);

        if ($attributes === []) {
            throw new CommandNameNotDeclaredException($command);
        }

        return $attributes[0]->newInstance()->name;
    }
}
