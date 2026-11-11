<?php

namespace Lomkit\Osdd\Layers;

use JsonException;

final class LayerManifest
{
    public const string LAYER_TYPE = 'layer';

    /**
     * The layer a composer.json describes, or null when it is a valid composer.json of another type.
     *
     * @throws InvalidLayerManifestException
     */
    public static function parse(string $json, string $directory): ?Layer
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidLayerManifestException(self::file($directory), "it is not valid JSON ({$exception->getMessage()}).");
        }

        if (! is_array($data)) {
            throw new InvalidLayerManifestException(self::file($directory), 'it is not a JSON object.');
        }

        return self::fromData($data, $directory);
    }

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws InvalidLayerManifestException
     */
    public static function fromData(array $data, string $directory): ?Layer
    {
        if (($data['type'] ?? null) !== self::LAYER_TYPE) {
            return null;
        }

        $reader = new ManifestReader($data, self::file($directory));

        return new Layer(
            name: $reader->name(),
            directory: new LayerDirectory($directory),
            autoload: $reader->psr4('autoload'),
            devAutoload: $reader->psr4('autoload-dev'),
            providers: $reader->providers(),
            requires: $reader->packages('require'),
            devRequires: $reader->packages('require-dev'),
        );
    }

    private static function file(string $directory): string
    {
        return (new LayerDirectory($directory))->manifestFile();
    }
}
