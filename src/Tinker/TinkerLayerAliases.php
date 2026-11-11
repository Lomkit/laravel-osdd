<?php

namespace Lomkit\Osdd\Tinker;

use Illuminate\Contracts\Config\Repository;
use Lomkit\Osdd\Layers\Layers;
use Symfony\Component\Finder\Finder;

final class TinkerLayerAliases
{
    public function __construct(
        private readonly Repository $config,
        private readonly Layers $layers,
    ) {}

    public function __invoke(): void
    {
        [$namespaces, $map] = $this->buildLayerClassMap();

        /** @var array<int, string> $aliases */
        $aliases = $this->config->get('tinker.alias', []);

        $this->config->set('tinker.alias', array_merge($aliases, $namespaces));

        spl_autoload_register(function (string $class) use ($map): void {
            if (str_contains($class, '\\') || ! isset($map[$class])) {
                return;
            }

            require_once $map[$class]['path'];
            class_alias($map[$class]['fqcn'], $class);
        });
    }

    /**
     * @return array{list<string>, array<string, array{fqcn: string, path: string}>}
     */
    private function buildLayerClassMap(): array
    {
        $namespaces = [];
        $map = [];

        foreach ($this->layers as $layer) {
            $namespace = $layer->rootNamespace;
            $srcPath = $layer->sourceDirectory;

            $namespaces[] = $namespace;

            if (! is_dir($srcPath)) {
                continue;
            }

            foreach ((new Finder)->files()->name('*.php')->in($srcPath) as $file) {
                $relative = substr($file->getRealPath(), strlen($srcPath) + 1);
                $fqcn = $namespace.str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
                $shortName = basename($relative, '.php');

                $map[$shortName] ??= ['fqcn' => $fqcn, 'path' => $file->getRealPath()];
            }
        }

        return [$namespaces, $map];
    }
}
