<?php

namespace Lomkit\Osdd\Tests\Fixtures;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class TemporaryProject
{
    private const array WORKBENCH_FILES = [
        'config/osdd.php',
        'phpunit.xml',
    ];

    private const array WORKBENCH_LAYER_FILES = [
        'functional/test-layer/composer.json',
        'functional/test-layer/database/migrations/.gitkeep',
    ];

    private const array DIRECTORIES = [
        'bootstrap/cache',
        'database/migrations',
        'resources/views',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
    ];

    private function __construct(
        public readonly string $root,
        private readonly Filesystem $files,
    ) {}

    public static function empty(): self
    {
        $files = new Filesystem;
        $project = new self((string) realpath(sys_get_temp_dir()).'/osdd-project-'.Str::lower(Str::random(12)), $files);
        $files->ensureDirectoryExists($project->root);

        return $project;
    }

    public static function withoutLayers(): self
    {
        $project = self::empty();

        foreach (self::DIRECTORIES as $directory) {
            $project->files->ensureDirectoryExists($project->path($directory));
        }

        return $project->copyFromWorkbench(self::WORKBENCH_FILES);
    }

    public static function fromWorkbench(): self
    {
        return self::withoutLayers()->copyFromWorkbench(self::WORKBENCH_LAYER_FILES);
    }

    public function path(string $relative = ''): string
    {
        return $relative === '' ? $this->root : $this->root.'/'.$relative;
    }

    public function delete(): void
    {
        $this->files->deleteDirectory($this->root);
    }

    /**
     * @param  list<string>  $files
     */
    private function copyFromWorkbench(array $files): self
    {
        foreach ($files as $file) {
            $this->files->ensureDirectoryExists(dirname($this->path($file)));
            $this->files->copy(dirname(__DIR__, 2).'/workbench/'.$file, $this->path($file));
        }

        return $this;
    }
}
