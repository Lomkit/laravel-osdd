<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Filesystem\Filesystem;
use stdClass;

final class ComposerJsonFile
{
    private const array REQUIRE_SECTIONS = ['require', 'require-dev'];

    private function __construct(
        private readonly Filesystem $files,
        private readonly ComposerJson $json,
    ) {}

    /**
     * @throws MalformedComposerJsonException
     */
    public static function open(Filesystem $files, string $path): self
    {
        return new self($files, ComposerJson::decode($files->get($path), $path));
    }

    public static function create(Filesystem $files, string $path): self
    {
        return new self($files, ComposerJson::blank($path));
    }

    public function save(): void
    {
        $this->files->put($this->json->path, $this->json->encode());
    }

    public function requirePackage(string $name, string $constraint): void
    {
        $require = $this->json->object('require');

        if (isset($require->{$name})) {
            return;
        }

        $require->{$name} = $constraint;

        if ($this->sortsPackages()) {
            /** @var array<string, mixed> $packages */
            $packages = (array) $require;
            $this->json->set('require', (object) ComposerPackageOrder::sort($packages));
        }
    }

    public function addPathRepository(PathRepository $wanted): void
    {
        (new ComposerRepositories($this->json))->addPath($wanted);
    }

    public function removePathRepository(PathRepository $unwanted): void
    {
        (new ComposerRepositories($this->json))->removePath($unwanted);
    }

    /**
     * @return list<PathRepository>
     */
    public function pathRepositories(): array
    {
        return (new ComposerRepositories($this->json))->paths();
    }

    public function removePackage(string $name): void
    {
        foreach (self::REQUIRE_SECTIONS as $section) {
            $links = $this->json->find($section);

            if ($links instanceof stdClass) {
                unset($links->{$name});
            }
        }
    }

    /**
     * @return list<string>
     */
    public function requiredPackages(): array
    {
        $packages = [];

        foreach (self::REQUIRE_SECTIONS as $section) {
            $links = $this->json->find($section);
            $packages = [...$packages, ...array_map(strval(...), array_keys(is_array($links) || $links instanceof stdClass ? (array) $links : []))];
        }

        return $packages;
    }

    /**
     * Add the keys of $defaults that this file does not have yet, at every depth; existing values win.
     */
    public function addMissing(stdClass $defaults): void
    {
        MissingKeys::copy($defaults, $this->json->object());
    }

    public function addLaravelProvider(string $provider): void
    {
        $laravel = $this->json->object('extra', 'laravel');
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
        $providers = $this->json->find('extra', 'laravel', 'providers');

        return is_array($providers) ? array_values(array_filter($providers, is_string(...))) : [];
    }

    public function removeAutoloadNamespace(string $namespace): void
    {
        foreach (['autoload', 'autoload-dev'] as $section) {
            $psr4 = $this->json->find($section, 'psr-4');

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
        $psr4 = $this->json->find('autoload', 'psr-4');
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
        return $this->json->find('config', 'sort-packages') === true;
    }
}
