<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Support\Path;
use stdClass;

final class ComposerJsonFile
{
    private const int ENCODING = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    private const string DEFAULT_INDENT = '    ';

    private function __construct(
        private readonly Filesystem $files,
        public readonly string $path,
        private readonly stdClass $contents,
        private readonly string $indent,
    ) {}

    public static function open(Filesystem $files, string $path): self
    {
        $json = $files->get($path);
        $contents = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (! $contents instanceof stdClass) {
            throw new InvalidComposerJsonException($path, 'root');
        }

        $indent = preg_match('#^([ \t]+)"#m', $json, $match) === 1 ? $match[1] : self::DEFAULT_INDENT;

        return new self($files, $path, $contents, $indent);
    }

    public function save(): void
    {
        $json = json_encode($this->contents, self::ENCODING);

        if ($this->indent !== self::DEFAULT_INDENT) {
            $json = (string) preg_replace_callback(
                '#^(?: {4})+#m',
                fn (array $spaces): string => str_repeat($this->indent, intdiv(strlen($spaces[0]), 4)),
                $json,
            );
        }

        $this->files->put($this->path, $json.PHP_EOL);
    }

    public function requirePackage(string $name, string $constraint): void
    {
        $require = $this->object($this->contents, 'require');

        if (isset($require->{$name})) {
            return;
        }

        $require->{$name} = $constraint;

        if ($this->sortsPackages()) {
            /** @var array<string, mixed> $packages */
            $packages = (array) $require;
            $this->contents->require = (object) ComposerPackageOrder::sort($packages);
        }
    }

    public function addPathRepository(string $url): void
    {
        $repositories = $this->contents->repositories ?? [];
        $wanted = $this->resolveUrl($url);

        foreach (is_array($repositories) || $repositories instanceof stdClass ? (array) $repositories : [] as $repository) {
            if ($repository instanceof stdClass && is_string($repository->url ?? null) && $this->resolveUrl($repository->url) === $wanted) {
                return;
            }
        }

        $repository = (object) ['type' => 'path', 'url' => $url];

        if ($repositories instanceof stdClass) {
            $repositories->{$url} = $repository;

            return;
        }

        $this->contents->repositories = [...(is_array($repositories) ? $repositories : []), $repository];
    }

    /**
     * Add the keys of $json that this file does not have yet, at every depth; existing values win.
     */
    public function addMissing(string $json): void
    {
        $defaults = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (! $defaults instanceof stdClass) {
            throw new InvalidComposerJsonException($this->path, 'root');
        }

        MissingKeys::copy($defaults, $this->contents);
    }

    public function addLaravelProvider(string $provider): void
    {
        $laravel = $this->object($this->object($this->contents, 'extra'), 'laravel');
        $providers = $this->laravelProviders();

        if (! in_array($provider, $providers, true)) {
            $laravel->providers = [...$providers, $provider];
        }
    }

    /**
     * @return list<string>
     */
    public function laravelProviders(): array
    {
        $providers = $this->find('extra', 'laravel', 'providers');

        return is_array($providers) ? array_values(array_filter($providers, is_string(...))) : [];
    }

    public function removeAutoloadNamespace(string $namespace): void
    {
        foreach (['autoload', 'autoload-dev'] as $section) {
            $psr4 = $this->find($section, 'psr-4');

            if ($psr4 instanceof stdClass) {
                unset($psr4->{$namespace});
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function psr4(): array
    {
        $psr4 = $this->find('autoload', 'psr-4');
        $namespaces = [];

        foreach ($psr4 instanceof stdClass ? (array) $psr4 : [] as $namespace => $directory) {
            if (is_string($directory)) {
                $namespaces[(string) $namespace] = $directory;
            }
        }

        return $namespaces;
    }

    private function sortsPackages(): bool
    {
        return $this->find('config', 'sort-packages') === true;
    }

    private function resolveUrl(string $url): string
    {
        $directory = dirname($this->path);

        return Path::normalize(Path::isAbsolute($url) ? $url : $directory.'/'.$url);
    }

    private function find(string ...$keys): mixed
    {
        $node = $this->contents;

        foreach ($keys as $key) {
            if (! $node instanceof stdClass || ! property_exists($node, $key)) {
                return null;
            }

            $node = $node->{$key};
        }

        return $node;
    }

    private function object(stdClass $parent, string $key): stdClass
    {
        $value = $parent->{$key} ?? new stdClass;

        if ($value === []) {
            $value = new stdClass;
        }

        if (! $value instanceof stdClass) {
            throw new InvalidComposerJsonException($this->path, $key);
        }

        return $parent->{$key} = $value;
    }
}
