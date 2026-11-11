<?php

namespace Lomkit\Osdd\Console\Providers;

use Illuminate\Contracts\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;
use Lomkit\Osdd\Layers\Layer;

final class BootedLayerResources
{
    public function __construct(
        private readonly Factory $views,
        private readonly BladeCompiler $blade,
    ) {}

    public function viewNamespace(Layer $layer): ?string
    {
        $finder = $this->views->getFinder();
        $directory = realpath($layer->path.'/resources/views');

        if (! $finder instanceof FileViewFinder || $directory === false) {
            return null;
        }

        foreach ($finder->getHints() as $namespace => $paths) {
            if ($this->containsDirectory((array) $paths, $directory)) {
                return (string) $namespace;
            }
        }

        return null;
    }

    public function componentPrefix(string $componentNamespace): ?string
    {
        foreach ($this->blade->getClassComponentNamespaces() as $prefix => $namespace) {
            if (is_string($namespace) && trim($namespace, '\\') === trim($componentNamespace, '\\')) {
                return (string) $prefix;
            }
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $paths
     */
    private function containsDirectory(array $paths, string $directory): bool
    {
        foreach ($paths as $path) {
            if (is_string($path) && realpath($path) === $directory) {
                return true;
            }
        }

        return false;
    }
}
