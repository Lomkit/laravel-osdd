<?php

namespace Lomkit\Osdd\Console;

use Illuminate\Filesystem\Filesystem;

final class StubWriter
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  string  $stub  Path under src/Console/stubs, without the .stub extension (layer/composer)
     * @param  array<string, string>  $replacements
     */
    public function write(string $stub, string $target, array $replacements = []): void
    {
        $this->files->ensureDirectoryExists(dirname($target));

        $this->files->put($target, $this->render($stub, $replacements));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(string $stub, array $replacements = []): string
    {
        $contents = $this->files->get(__DIR__.'/stubs/'.$stub.'.stub');

        return str_replace(array_keys($replacements), array_values($replacements), $contents);
    }
}
