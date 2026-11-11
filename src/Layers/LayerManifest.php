<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Filesystem\Filesystem;

class LayerManifest
{
    private const string DEFAULT_SOURCE_DIRECTORY = 'src/';

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
        return $this->sourceMapping()[0];
    }

    public function srcPath(string $layerPath): string
    {
        return rtrim($layerPath, '/').'/'.trim($this->sourceMapping()[1], '/');
    }

    /**
     * The shortest psr-4 prefix, so a Database\Factories\ entry listed first is not taken for the source.
     *
     * @return array{string, string}
     */
    private function sourceMapping(): array
    {
        $psr4 = $this->data['autoload']['psr-4'] ?? [];

        if ($psr4 === []) {
            return ['', self::DEFAULT_SOURCE_DIRECTORY];
        }

        $namespaces = array_keys($psr4);
        usort($namespaces, fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        return [$namespaces[0], $psr4[$namespaces[0]]];
    }
}
