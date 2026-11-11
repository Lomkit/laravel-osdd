<?php

namespace Lomkit\Osdd\Scaffolding;

interface LayerScaffold
{
    public function key(): string;

    /**
     * The keys of the scaffolds that must run first when they are part of the same plan.
     *
     * @return list<string>
     */
    public function dependsOn(): array;

    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void;
}
