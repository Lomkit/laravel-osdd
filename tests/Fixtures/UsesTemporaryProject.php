<?php

namespace Lomkit\Osdd\Tests\Fixtures;

use PHPUnit\Framework\Attributes\After;

trait UsesTemporaryProject
{
    private ?TemporaryProject $temporaryProject = null;

    protected function project(): TemporaryProject
    {
        return $this->temporaryProject ??= TemporaryProject::empty();
    }

    #[After]
    protected function deleteTemporaryProject(): void
    {
        $this->temporaryProject?->delete();
        $this->temporaryProject = null;
    }
}
