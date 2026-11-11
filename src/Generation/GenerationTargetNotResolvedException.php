<?php

namespace Lomkit\Osdd\Generation;

use LogicException;

final class GenerationTargetNotResolvedException extends LogicException
{
    /**
     * @param  class-string  $command
     */
    public function __construct(public readonly string $command)
    {
        parent::__construct("Command [{$command}] has no target layer outside of a run.");
    }
}
