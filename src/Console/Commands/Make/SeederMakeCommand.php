<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'osdd:seeder')]
class SeederMakeCommand extends \Illuminate\Database\Console\Seeds\SeederMakeCommand
{
    use ChoosesOsddLayer {
        configureUsingFluentDefinition as configureLayerDefinition;
    }
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
        $loader = $this->option('tenant') ? 'loadTenantSeeders' : 'loadSeeders';

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (): string => "\$this->{$loader}([\\{$seederClass}::class]);",
            $seederClass,
        );
    }

    #[\Override]
    protected function configureUsingFluentDefinition(): void
    {
        $this->configureLayerDefinition();

        $this->getDefinition()->addOption(new InputOption('tenant', null, InputOption::VALUE_NONE, 'Register the seeder for the tenant databases with loadTenantSeeders()'));
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
