<?php

namespace Lomkit\Osdd\Console\Phpunit;

use Lomkit\Osdd\Filesystem\Path;

final readonly class DirectoryPattern
{
    private const string GLOB_CHARACTERS = '*?[';

    private const string GLOBSTAR = '**';

    private function __construct(private string $path) {}

    /**
     * A <directory> value of phpunit.xml, read as PHPUnit reads it: trimmed, and relative to the phpunit.xml folder
     * unless absolute.
     */
    public static function of(string $value, string $phpunitDirectory): self
    {
        $value = trim($value);

        return new self(Path::isAbsolute($value) || str_contains($value, '://') ? $value : $phpunitDirectory.'/'.$value);
    }

    public function hasGlob(): bool
    {
        return strpbrk($this->path, self::GLOB_CHARACTERS) !== false;
    }

    public function isWrittenLike(self $other): bool
    {
        return Path::normalize($this->path) === Path::normalize($other->path);
    }

    /**
     * The real folders PHPUnit's file iterator reads for this value: glob() with "**" for zero or more folders, each
     * match through realpath.
     *
     * @return list<string>
     */
    public function folders(): array
    {
        $matches = self::glob($this->path);
        $folders = [];

        foreach ($matches === [] ? [$this->path] : $matches as $match) {
            $folder = realpath($match);

            if ($folder !== false && is_dir($folder)) {
                $folders[] = $folder;
            }
        }

        return array_values(array_unique($folders));
    }

    /**
     * @return list<string>
     */
    private static function glob(string $pattern): array
    {
        $position = strpos($pattern, self::GLOBSTAR);

        if ($position === false) {
            return glob($pattern, GLOB_ONLYDIR) ?: [];
        }

        $root = substr($pattern, 0, $position);
        $rest = substr($pattern, $position + strlen(self::GLOBSTAR));
        $patterns = [$root.ltrim($rest, '/')];

        for ($depth = $root.'*'; ($directories = glob($depth, GLOB_ONLYDIR) ?: []) !== []; $depth .= '/*') {
            foreach ($directories as $directory) {
                $patterns[] = $directory.$rest;
            }
        }

        return array_merge(...array_map(self::glob(...), $patterns));
    }
}
