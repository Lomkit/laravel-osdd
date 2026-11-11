<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Support\Str;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:provider')]
class ServiceProviderMakeCommand extends ProviderMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:provider';

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Providers';
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\Providers\\', '', $name);

        return $layer->path.'/src/Providers/'.str_replace('\\', '/', $relative).'.php';
    }

    #[\Override]
    public function handle(): ?bool
    {
        $result = parent::handle();

        if ($result === false) {
            return false;
        }

        $this->registerProviderInComposer();

        return $result;
    }

    protected function registerProviderInComposer(): void
    {
        $layer = $this->resolveLayer();
        $composerPath = $layer->path.'/composer.json';
        $providerClass = $this->qualifyClass($this->getNameInput());

        $composer = ComposerJsonFile::open($this->files, $composerPath);
        $composer->addLaravelProvider($providerClass);
        $composer->save();

        $this->components->info("Provider [{$providerClass}] registered in [{$composerPath}].");
    }
}
