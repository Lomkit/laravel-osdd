<?php

namespace Lomkit\Osdd\Console;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Layers\LayerName;

final class LayerComposerJson
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly StubWriter $stubs,
    ) {}

    /**
     * A new layer gets the stub; an existing composer.json keeps its content and only gains the stub keys it lacks.
     */
    public function write(LayerName $name, string $path): void
    {
        $replacements = [
            '{{ name }}' => $name->toString(),
            '{{ namespace }}' => str_replace('\\', '\\\\', $name->namespace()),
        ];

        if (! $this->files->exists($path)) {
            $this->stubs->write('layer/composer', $path, $replacements);

            return;
        }

        $composer = ComposerJsonFile::open($this->files, $path);
        $composer->addMissing($this->stubs->render('layer/composer', $replacements));
        $composer->save();
    }
}
