<?php

namespace Lomkit\Osdd\Filesystem;

final class Path
{
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $root = self::root($path);
        $segments = [];

        foreach (explode('/', substr($path, strlen($root))) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..' && $segments !== [] && end($segments) !== '..') {
                array_pop($segments);

                continue;
            }

            if ($segment === '..' && $root !== '') {
                continue;
            }

            $segments[] = $segment;
        }

        return $root.implode('/', $segments);
    }

    public static function isAbsolute(string $path): bool
    {
        return self::root(str_replace('\\', '/', $path)) !== '';
    }

    /**
     * The path of $target seen from the $base directory, with forward slashes.
     * Paths on different roots (two Windows drives) stay absolute.
     */
    public static function relative(string $base, string $target): string
    {
        $base = self::normalize($base);
        $target = self::normalize($target);

        if (strcasecmp(self::root($base), self::root($target)) !== 0) {
            return $target;
        }

        $baseSegments = self::segments($base);
        $targetSegments = self::segments($target);

        while ($baseSegments !== [] && $targetSegments !== [] && $baseSegments[0] === $targetSegments[0]) {
            array_shift($baseSegments);
            array_shift($targetSegments);
        }

        return implode('/', [...array_fill(0, count($baseSegments), '..'), ...$targetSegments]);
    }

    /**
     * @return list<string>
     */
    private static function segments(string $normalized): array
    {
        $relative = substr($normalized, strlen(self::root($normalized)));

        return $relative === '' ? [] : explode('/', $relative);
    }

    private static function root(string $path): string
    {
        if (preg_match('#^[A-Za-z]:/#', $path) === 1) {
            return substr($path, 0, 3);
        }

        return str_starts_with($path, '/') ? '/' : '';
    }
}
