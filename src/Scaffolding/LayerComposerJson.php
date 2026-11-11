<?php

namespace Lomkit\Osdd\Scaffolding;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Stubs\StubWriter;

final class LayerComposerJson
{
    private const string LAYER_CONSTRAINT = '*';

    public function __construct(
        private readonly Filesystem $files,
        private readonly StubWriter $stubs,
    ) {}

    /**
     * A new layer gets the stub; an existing composer.json keeps its content and only gains the stub keys it lacks.
     *
     * @param  list<string>  $requiredLayers
     */
    public function write(LayerName $name, string $path, array $requiredLayers = []): void
    {
        $replacements = [
            '{{ name }}' => $name->toString(),
            '{{ namespace }}' => str_replace('\\', '\\\\', $name->namespace()),
        ];
        $isNew = ! $this->files->exists($path);

        if ($isNew) {
            $this->stubs->write('layer/composer', $path, $replacements);
        }

        if (! $isNew || $requiredLayers !== []) {
            $composer = ComposerJsonFile::open($this->files, $path);
            $composer->addMissing($this->stubs->render('layer/composer', $replacements));

            foreach ($requiredLayers as $requiredLayer) {
                $composer->requirePackage($requiredLayer, self::LAYER_CONSTRAINT);
            }

            $composer->save();
        }
    }
}
