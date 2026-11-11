<?php

namespace Lomkit\Osdd\Console\Providers\Calls;

use Lomkit\Osdd\Layers\LayerFolder;

final readonly class LayerPathArgument implements CallArgument
{
    private function __construct(private string $layerRelativePath) {}

    public static function folder(LayerFolder $folder): self
    {
        return new self($folder->value);
    }

    public static function file(LayerFolder $folder, string $file): self
    {
        return new self($folder->value.'/'.$file);
    }

    #[\Override]
    public function render(ProviderFile $file): string
    {
        return $file->pathExpression($this->layerRelativePath);
    }
}
