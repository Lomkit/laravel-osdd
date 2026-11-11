<?php

namespace Lomkit\Osdd\Tests\Scaffolding\Fixtures;

use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;

final class KeyedScaffold implements LayerScaffold
{
    /**
     * @var list<string>
     */
    public static array $runs = [];

    /**
     * @param  list<string>  $dependencies
     */
    public function __construct(
        private readonly string $key,
        private readonly array $dependencies = [],
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function dependsOn(): array
    {
        return $this->dependencies;
    }

    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void
    {
        self::$runs[] = $this->key.($context->has('model') ? '+model' : '');
    }
}
