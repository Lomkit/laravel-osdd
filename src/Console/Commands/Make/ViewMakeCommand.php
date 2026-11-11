<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:view')]
class ViewMakeCommand extends \Illuminate\Foundation\Console\ViewMakeCommand
{
    use ChoosesOsddLayer;
    use InjectsResourceHelpers;

    protected $name = 'osdd:view';

    #[\Override]
    public function handle(): ?bool
    {
        $created = parent::handle();

        $this->injectWithViews();

        return $created;
    }

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();

        $nameInput = str_replace(['\\', '.'], '/', trim($this->argument('name')));

        return $layer->path.'/resources/views/'.$nameInput.'.'.$this->option('extension');
    }
}
