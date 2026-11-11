<?php

namespace Lomkit\Osdd\Stubs;

use Illuminate\Filesystem\Filesystem;

final class StubWriter
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  array<string, string>  $replacements
     */
    public function write(Stub $stub, string $target, array $replacements = []): void
    {
        $this->files->ensureDirectoryExists(dirname($target));

        $this->files->put($target, $this->render($stub, $replacements));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(Stub $stub, array $replacements = []): string
    {
        $contents = $this->files->get($stub->path());

        return str_replace(array_keys($replacements), array_values($replacements), $contents);
    }
}
