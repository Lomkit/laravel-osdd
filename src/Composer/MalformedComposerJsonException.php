<?php

namespace Lomkit\Osdd\Composer;

use RuntimeException;

class MalformedComposerJsonException extends RuntimeException
{
    public function __construct(public readonly string $path, public readonly string $reason)
    {
        parent::__construct("Cannot read [{$path}]: {$reason}");
    }

    public static function invalidJson(string $path, string $error): self
    {
        return new self($path, "it is not valid JSON ({$error}).");
    }

    public static function notAnObject(string $path): self
    {
        return new self($path, 'it is not a JSON object.');
    }
}
