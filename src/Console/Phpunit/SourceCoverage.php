<?php

namespace Lomkit\Osdd\Console\Phpunit;

final readonly class SourceCoverage
{
    /**
     * @param  list<SourceDirectory>  $entries
     */
    public function __construct(private array $entries) {}

    /**
     * Whether an entry is the same path written another way, or the entries already read every .php file of each
     * folder the directory resolves to.
     */
    public function covers(DirectoryPattern $directory): bool
    {
        foreach ($this->entries as $entry) {
            if ($entry->pattern->isWrittenLike($directory)) {
                return true;
            }
        }

        $wanted = $directory->folders();
        $covered = array_merge(...array_map(fn (SourceDirectory $entry): array => $entry->coveredFolders(), $this->entries));

        foreach ($wanted as $folder) {
            if (! self::isWithin($folder, $covered)) {
                return false;
            }
        }

        return $wanted !== [];
    }

    /**
     * @param  list<string>  $folders
     */
    private static function isWithin(string $folder, array $folders): bool
    {
        foreach ($folders as $candidate) {
            if ($folder === $candidate || str_starts_with($folder, rtrim($candidate, '/\\').DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
