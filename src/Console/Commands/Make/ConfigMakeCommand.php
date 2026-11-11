<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:config')]
class ConfigMakeCommand extends \Illuminate\Foundation\Console\ConfigMakeCommand
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    protected $name = 'osdd:config';

    protected $aliases = [];

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->rootNamespace;
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();

        return $layer->directory->path(LayerFolder::Config).'/'.Str::finish($this->argument('name'), '.php');
    }

    #[\Override]
    public function handle(): ?bool
    {
        $result = parent::handle();

        $configFile = Str::finish($this->argument('name'), '.php');
        $configKey = Str::before($configFile, '.php');

        $this->registerInLayerProvider(
            ProviderMethod::Register,
            fn (LayerProvider $provider): string => "\$this->overrideConfigFrom({$provider->pathExpression(LayerFolder::Config->value.'/'.$configFile)}, '{$configKey}');",
            "config:{$configKey}",
        );

        return $result;
    }
}
