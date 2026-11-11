<?php

namespace Lomkit\Osdd\Tenancy;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;

final class ExposeTenantMigrationPaths
{
    private const string TARGET_KEY = 'osdd.tenancy.expose_migration_paths_to';

    public function __construct(
        private readonly Repository $config,
        private readonly TenantMigrationPaths $paths,
    ) {}

    public function __invoke(): void
    {
        $target = $this->config->get(self::TARGET_KEY);

        if (! is_string($target) || $target === '') {
            return;
        }

        $exposed = [...Arr::wrap($this->config->get($target)), ...$this->paths->all()];

        $this->config->set($target, array_values(array_unique($exposed, SORT_REGULAR)));
    }
}
