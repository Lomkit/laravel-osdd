<?php

namespace Lomkit\Osdd\Console\Migrations;

use Illuminate\Filesystem\Filesystem;

use function Illuminate\Filesystem\join_paths;

final class ExistingTableMigrations
{
    private const array SKELETON_MIGRATIONS = [
        'sessions' => '0001_01_01_000000_create_users_table.php',
        'jobs' => '0001_01_01_000002_create_jobs_table.php',
        'job_batches' => '0001_01_01_000002_create_jobs_table.php',
        'failed_jobs' => '0001_01_01_000002_create_jobs_table.php',
    ];

    public function __construct(private readonly Filesystem $files) {}

    /**
     * @param  list<string>  $directories
     */
    public function exists(string $table, array $directories): bool
    {
        foreach (array_unique($directories) as $directory) {
            foreach ($this->patterns($table) as $pattern) {
                if ($this->files->glob(join_paths($directory, $pattern)) !== []) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function patterns(string $table): array
    {
        $skeleton = self::SKELETON_MIGRATIONS[$table] ?? null;

        return [
            '*_*_*_*_create_'.$table.'_table.php',
            ...($skeleton === null ? [] : [$skeleton]),
        ];
    }
}
