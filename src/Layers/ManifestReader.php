<?php

namespace Lomkit\Osdd\Layers;

final class ManifestReader
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
        private readonly string $file,
    ) {}

    /**
     * @throws InvalidLayerManifestException
     */
    public function name(): LayerName
    {
        $name = $this->data['name'] ?? null;

        if (! is_string($name)) {
            throw $this->invalid('it has no "name".');
        }

        try {
            return LayerName::fromString($name);
        } catch (InvalidLayerNameException $exception) {
            throw $this->invalid($exception->getMessage());
        }
    }

    /**
     * @return array<string, string>
     *
     * @throws InvalidLayerManifestException
     */
    public function psr4(string $section): array
    {
        $mapping = $this->section($section)['psr-4'] ?? [];

        if (! is_array($mapping)) {
            throw $this->invalid("\"{$section}.psr-4\" must map namespaces to directories.");
        }

        $directories = [];

        foreach ($mapping as $namespace => $directory) {
            $directories[(string) $namespace] = $this->directory($section, $directory);
        }

        return $directories;
    }

    /**
     * @return list<string>
     *
     * @throws InvalidLayerManifestException
     */
    public function packages(string $section): array
    {
        $packages = $this->data[$section] ?? [];

        if (! is_array($packages)) {
            throw $this->invalid("\"{$section}\" must map package names to constraints.");
        }

        return array_map(strval(...), array_keys($packages));
    }

    /**
     * @return list<string>
     *
     * @throws InvalidLayerManifestException
     */
    public function providers(): array
    {
        $laravel = $this->section('extra')['laravel'] ?? [];
        $providers = is_array($laravel) ? ($laravel['providers'] ?? []) : null;

        if (! is_array($providers) || ! array_is_list($providers) || array_filter($providers, is_string(...)) !== $providers) {
            throw $this->invalid('"extra.laravel.providers" must be a list of class names.');
        }

        return $providers;
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws InvalidLayerManifestException
     */
    private function section(string $section): array
    {
        $value = $this->data[$section] ?? [];

        if (! is_array($value)) {
            throw $this->invalid("\"{$section}\" must be an object.");
        }

        return $value;
    }

    /**
     * @throws InvalidLayerManifestException
     */
    private function directory(string $section, mixed $directory): string
    {
        $first = is_array($directory) ? (array_values($directory)[0] ?? null) : $directory;

        if (! is_string($first)) {
            throw $this->invalid("\"{$section}.psr-4\" must map namespaces to directories.");
        }

        return $first;
    }

    private function invalid(string $reason): InvalidLayerManifestException
    {
        return new InvalidLayerManifestException($this->file, $reason);
    }
}
