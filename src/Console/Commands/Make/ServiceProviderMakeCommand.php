<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Support\Str;
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

        /** @var array<string, mixed> $composer */
        $composer = json_decode($this->files->get($composerPath), true);

        $providerClass = $this->qualifyClass($this->getNameInput());

        /** @var array<string, mixed> $extra */
        $extra = $composer['extra'] ?? [];

        /** @var array<string, mixed> $laravel */
        $laravel = $extra['laravel'] ?? [];

        /** @var list<string> $existing */
        $existing = $laravel['providers'] ?? [];
        $existing[] = $providerClass;

        $laravel['providers'] = array_values(array_unique($existing));
        $extra['laravel'] = $laravel;
        $composer['extra'] = $extra;

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        $this->components->info("Provider [{$providerClass}] registered in [{$composerPath}].");
    }
}
