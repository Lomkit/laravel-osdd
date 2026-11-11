<?php

namespace Lomkit\Osdd\Tests\Fixtures;

use Illuminate\Foundation\Application;

final readonly class ProviderFile
{
    /**
     * @param  class-string  $class
     */
    public function __construct(
        public string $class,
        public string $path,
    ) {}

    public function source(): string
    {
        return (string) file_get_contents($this->path);
    }

    public function register(Application $app): void
    {
        require_once $this->path;

        $app->register($this->class);
    }
}
