<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:seeder')]
class SeederMakeCommand extends \Illuminate\Database\Console\Seeds\SeederMakeCommand
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:seeder';

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        $seederClass = $this->qualifyClass($this->getNameInput());

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->loadSeeders([\\{$seederClass}::class]);",
            $seederClass,
        );
    }

    #[\Override]
    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\').'\\Database\\Seeders';
    }

    #[\Override]
    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace.'\\Database\\Seeders\\', '', $name);

        return $layer->path.'/database/seeders/'.str_replace('\\', '/', $relative).'.php';
    }
}
