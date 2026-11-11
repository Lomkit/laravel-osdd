<?php

namespace Lomkit\Osdd\Layers;

use ReflectionClass;

final class ProviderLayerManifest
{
    /**
     * @var array<class-string, LayerManifest>
     */
    private static array $manifests = [];

    /**
     * The composer.json closest to the provider's class file.
     *
     * @param  class-string  $provider
     *
     * @throws LayerManifestNotFoundException
     */
    public static function of(string $provider): LayerManifest
    {
        return self::$manifests[$provider] ??= LayerManifest::fromPath(self::composerPath($provider));
    }

    /**
     * @param  class-string  $provider
     */
    private static function composerPath(string $provider): string
    {
        $file = (string) (new ReflectionClass($provider))->getFileName();
        $directory = dirname($file);

        while (! is_file($directory.'/composer.json')) {
            $parent = dirname($directory);

            if ($parent === $directory) {
                throw new LayerManifestNotFoundException($provider, $file);
            }

            $directory = $parent;
        }

        return $directory.'/composer.json';
    }
}
