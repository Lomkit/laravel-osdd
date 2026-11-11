<?php

namespace Lomkit\Osdd\Inventory;

use Lomkit\Osdd\Layers\InvalidLayerManifestException;

final readonly class InvalidManifestSummary
{
    private function __construct(
        public string $file,
        public string $error,
    ) {}

    public static function of(InvalidLayerManifestException $exception, RootProject $project): self
    {
        return new self($project->relativePath($exception->manifestFile), $exception->reason);
    }

    /**
     * @return array{file: string, error: string}
     */
    public function toArray(): array
    {
        return ['file' => $this->file, 'error' => $this->error];
    }
}
