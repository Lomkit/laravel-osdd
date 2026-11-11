<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\Statements\OverrideConfig;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:config')]
final class ConfigMakeCommand extends \Illuminate\Foundation\Console\ConfigMakeCommand
{
    use GeneratesClassesInLayer;

    #[\Override]
    public function handle(): ?bool
    {
        $result = parent::handle();

        $this->wireInTargetLayer(new OverrideConfig($this->configFile()));

        return $result;
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    protected function getPath($name): string
    {
        return $this->target()->layer->directory->path(LayerFolder::Config).'/'.$this->configFile();
    }

    private function configFile(): string
    {
        return Str::finish($this->argument('name'), '.php');
    }
}
