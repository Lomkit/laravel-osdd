<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:view')]
final class ViewMakeCommand extends \Illuminate\Foundation\Console\ViewMakeCommand
{
    use GeneratesInLayer;
    use InjectsResourceHelpers;

    #[\Override]
    public function handle(): ?bool
    {
        $this->warnWhenTheResourceNamespaceIsShared();

        $created = parent::handle();

        $this->injectWithViews();

        return $created;
    }

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->target()->layer->rootNamespace;
    }

    #[\Override]
    protected function viewPath($path = ''): string
    {
        return $this->target()->viewPath($path);
    }
}
