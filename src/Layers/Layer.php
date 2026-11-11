<?php

namespace Lomkit\Osdd\Layers;

final readonly class Layer
{
    private const string DEFAULT_SOURCE_DIRECTORY = 'src';

    public string $rootNamespace;

    public string $sourceDirectory;

    /**
     * @param  array<string, string>  $autoload  psr-4 prefix => directory relative to the layer root
     * @param  array<string, string>  $devAutoload  psr-4 prefix => directory relative to the layer root
     * @param  list<string>  $providers
     * @param  list<string>  $requires
     * @param  list<string>  $devRequires
     */
    public function __construct(
        public LayerName $name,
        public LayerDirectory $directory,
        public array $autoload,
        public array $devAutoload,
        public array $providers,
        public array $requires,
        public array $devRequires,
    ) {
        $sourceNamespace = $this->sourceNamespace();

        $this->rootNamespace = $sourceNamespace ?? '';
        $this->sourceDirectory = $directory->pathTo($sourceNamespace === null ? self::DEFAULT_SOURCE_DIRECTORY : $autoload[$sourceNamespace]);
    }

    /**
     * @return list<string>
     */
    public function namespaces(): array
    {
        return array_keys($this->autoload);
    }

    /**
     * @return list<string>
     */
    public function devNamespaces(): array
    {
        return array_keys($this->devAutoload);
    }

    /**
     * The file of a class under the longest matching psr-4 prefix, or null when no prefix matches.
     */
    public function classFile(string $class): ?string
    {
        $class = ltrim($class, '\\');
        $prefix = $this->longestPrefixOf($class);

        if ($prefix === null) {
            return null;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));

        return $this->directory->pathTo(trim($this->autoload[$prefix], '/').'/'.$relative.'.php');
    }

    /**
     * The length of the longest psr-4 prefix of the class, 0 when the layer does not own it.
     */
    public function ownershipOf(string $class): int
    {
        return strlen($this->longestPrefixOf(ltrim($class, '\\')) ?? '');
    }

    /**
     * @return array{name: string, type: string, autoload: array{psr-4: array<string, string>}, autoload-dev: array{psr-4: array<string, string>}, require: array<string, string>, require-dev: array<string, string>, extra: array{laravel: array{providers: list<string>}}}
     */
    public function toManifest(): array
    {
        return [
            'name' => $this->name->toString(),
            'type' => LayerManifest::LAYER_TYPE,
            'autoload' => ['psr-4' => $this->autoload],
            'autoload-dev' => ['psr-4' => $this->devAutoload],
            'require' => array_fill_keys($this->requires, '*'),
            'require-dev' => array_fill_keys($this->devRequires, '*'),
            'extra' => ['laravel' => ['providers' => $this->providers]],
        ];
    }

    private function longestPrefixOf(string $class): ?string
    {
        $longest = null;

        foreach ($this->namespaces() as $namespace) {
            if ($namespace !== '' && str_starts_with($class, $namespace) && strlen($namespace) > strlen($longest ?? '')) {
                $longest = $namespace;
            }
        }

        return $longest;
    }

    /**
     * The shortest psr-4 prefix, so a Database\Factories\ entry listed first is not taken for the source.
     */
    private function sourceNamespace(): ?string
    {
        $namespaces = $this->namespaces();

        if ($namespaces === []) {
            return null;
        }

        usort($namespaces, fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        return $namespaces[0];
    }
}
