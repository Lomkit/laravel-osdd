<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:config')]
final class ConfigMakeCommand extends \Illuminate\Foundation\Console\ConfigMakeCommand
{
    use GeneratesClassesInLayer;
    use RegistersInLayerProvider;

    #[\Override]
    public function handle(): ?bool
    {
        $result = parent::handle();

        $configFile = $this->configFile();
        $configKey = Str::before($configFile, '.php');

        $this->registerInLayerProvider(
            ProviderMethod::Register,
            fn (LayerProvider $provider): string => "\$this->overrideConfigFrom({$provider->pathExpression(LayerFolder::Config->value.'/'.$configFile)}, '{$configKey}');",
            "config:{$configKey}",
        );

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
