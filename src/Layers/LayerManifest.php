<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Filesystem\Filesystem;

class LayerManifest
{
    /**
     * @param  array{name: string, type?: string, autoload?: array{psr-4?: array<string, string>}}  $data
     */
    private function __construct(private readonly array $data) {}

    public static function fromPath(string $path): self
    {
        /** @var array{name: string, type?: string, autoload?: array{psr-4?: array<string, string>}} $data */
        $data = json_decode(app(Filesystem::class)->get($path), true, 512, JSON_THROW_ON_ERROR);

        return new self($data);
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function type(): string
    {
        return $this->data['type'] ?? '';
    }

    public function vendor(): string
    {
        return explode('/', $this->name())[0];
    }

    public function package(): string
    {
        return explode('/', $this->name())[1];
    }

    public function isLayer(): bool
    {
        return $this->type() === 'layer';
    }

    public function rootNamespace(): string
    {
        return array_key_first($this->data['autoload']['psr-4'] ?? []) ?? '';
    }

    public function srcPath(string $layerPath): string
    {
        $relative = current($this->data['autoload']['psr-4'] ?? ['src/']);

        return rtrim($layerPath, '/').'/'.trim($relative === false ? '' : $relative, '/');
    }
}
