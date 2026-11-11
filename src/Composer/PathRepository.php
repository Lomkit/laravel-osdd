<?php

namespace Lomkit\Osdd\Composer;

use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Layers\LayerDirectory;
use stdClass;

final readonly class PathRepository
{
    public const string TYPE = 'path';

    private const string EVERY_DIRECTORY = '*';

    private function __construct(
        public string $url,
        private string $pattern,
    ) {}

    public static function fromUrl(string $baseDirectory, string $url): self
    {
        return new self($url, Path::isAbsolute($url) ? $url : $baseDirectory.'/'.$url);
    }

    public static function forLayerDirectory(string $baseDirectory, LayerDirectory $directory): self
    {
        $bucket = Path::relative($baseDirectory, dirname($directory->root));

        $url = match (true) {
            $bucket === '' => './'.self::EVERY_DIRECTORY,
            Path::isAbsolute($bucket), $bucket === '..', str_starts_with($bucket, '../') => $bucket.'/'.self::EVERY_DIRECTORY,
            default => './'.$bucket.'/'.self::EVERY_DIRECTORY,
        };

        return self::fromUrl($baseDirectory, $url);
    }

    public function isSameAs(self $other): bool
    {
        return Path::normalize($this->pattern) === Path::normalize($other->pattern);
    }

    public function covers(string $directory): bool
    {
        $wanted = Path::normalize((string) realpath($directory));

        foreach (glob($this->pattern, GLOB_ONLYDIR) ?: [] as $covered) {
            if (Path::normalize((string) realpath($covered)) === $wanted) {
                return true;
            }
        }

        return false;
    }

    public function toObject(): stdClass
    {
        return (object) ['type' => self::TYPE, 'url' => $this->url];
    }
}
