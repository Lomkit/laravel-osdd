<?php

namespace Lomkit\Osdd\Generation;

use LogicException;

final class CommandNameNotDeclaredException extends LogicException
{
    /**
     * @param  class-string  $command
     */
    public function __construct(public readonly string $command)
    {
        parent::__construct("Command [{$command}] declares no #[AsCommand] name.");
    }
}
