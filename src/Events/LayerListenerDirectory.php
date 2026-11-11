<?php

namespace Lomkit\Osdd\Events;

use Illuminate\Support\Str;
use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\Layer;
use SplFileInfo;

final readonly class LayerListenerDirectory
{
    private const string FOLDER = 'Listeners';

    private function __construct(
        public string $path,
        private string $namespace,
    ) {}

    /**
     * The Listeners directory of the layer's source, or null when the layer has no psr-4 namespace to name its classes.
     */
    public static function of(Layer $layer): ?self
    {
        if ($layer->rootNamespace === '') {
            return null;
        }

        return new self($layer->sourceDirectory.'/'.self::FOLDER, $layer->rootNamespace.self::FOLDER.'\\');
    }

    public function exists(): bool
    {
        return is_dir($this->path);
    }

    /**
     * The class the file declares under the layer's psr-4 namespace. Laravel's event discovery skips the file on a
     * ReflectionException, as it does for a class that does not exist.
     *
     * @return class-string
     *
     * @throws ListenerClassNotFoundException
     */
    public function classOf(SplFileInfo $file): string
    {
        $relative = Path::relative((string) realpath($this->path), (string) $file->getRealPath());
        $class = $this->namespace.str_replace('/', '\\', Str::replaceLast('.php', '', $relative));

        if (! class_exists($class)) {
            throw new ListenerClassNotFoundException($file->getPathname(), $class);
        }

        return $class;
    }
}
