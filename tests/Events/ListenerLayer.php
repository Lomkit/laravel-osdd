<?php

namespace Lomkit\Osdd\Tests\Events;

use Composer\Autoload\ClassLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lomkit\Osdd\Tests\Fixtures\ListenerCalls;

final class ListenerLayer
{
    private static int $layers = 0;

    private function __construct(
        public readonly string $directory,
        public readonly string $namespace,
        public readonly string $sourceFolder,
        private readonly ClassLoader $loader,
    ) {}

    /**
     * A layer with an OrderPlaced event and a SendInvoice listener, under a namespace no other test of the process uses.
     */
    public static function create(string $basePath, string $sourceFolder = 'src'): self
    {
        $package = 'events'.++self::$layers;
        $namespace = 'Functional\\'.Str::studly($package).'\\';
        $directory = $basePath.'/functional/'.$package;
        $loader = new ClassLoader;
        $loader->addPsr4($namespace, $directory.'/'.$sourceFolder);
        $loader->register();

        $layer = new self($directory, $namespace, $sourceFolder, $loader);
        $layer->write('composer.json', (string) json_encode([
            'name' => 'functional/'.$package,
            'type' => 'layer',
            'autoload' => ['psr-4' => [$namespace => $sourceFolder.'/']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $layer->writeClass('Events\\OrderPlaced', 'class OrderPlaced {}');
        $layer->writeListener('SendInvoice');

        return $layer;
    }

    public function event(): string
    {
        return $this->namespace.'Events\\OrderPlaced';
    }

    public function listener(string $name = 'SendInvoice'): string
    {
        return $this->namespace.'Listeners\\'.$name;
    }

    /**
     * @param  list<string>  $methods  The methods handling the event, in their declaration order
     */
    public function writeListener(string $name, array $methods = ['handle'], ?string $event = null): void
    {
        $event = '\\'.($event ?? $this->event());
        $calls = '\\'.ListenerCalls::class;
        $short = class_basename(str_replace('\\', '/', $name));
        $bodies = array_map(
            fn (string $method): string => "    public function {$method}({$event} \$event): void\n    {\n        {$calls}::\$calls[] = static::class.'@{$method}';\n    }",
            $methods,
        );

        $this->writeClass('Listeners\\'.$name, "class {$short}\n{\n".implode("\n\n", $bodies)."\n}");
    }

    public function listenersDirectory(): string
    {
        return $this->directory.'/'.$this->sourceFolder.'/Listeners';
    }

    public function unregister(): void
    {
        $this->loader->unregister();
    }

    private function writeClass(string $relativeClass, string $body): void
    {
        $class = $this->namespace.$relativeClass;
        $namespace = Str::beforeLast($class, '\\');

        $this->write(
            $this->sourceFolder.'/'.str_replace('\\', '/', $relativeClass).'.php',
            "<?php\n\nnamespace {$namespace};\n\n{$body}\n",
        );
    }

    private function write(string $relativePath, string $contents): void
    {
        $files = new Filesystem;
        $files->ensureDirectoryExists(dirname($this->directory.'/'.$relativePath));
        $files->put($this->directory.'/'.$relativePath, $contents);
    }
}
