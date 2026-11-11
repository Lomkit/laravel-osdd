<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:migration')]
class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'osdd:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--fullpath : Output the full path of the migration (Deprecated)}
        {--layer= : The layer to generate the file in}';

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (LayerProvider $provider): string => "\$this->loadMigrationsFrom({$provider->pathExpression('database/migrations')});",
            'database/migrations',
        );
    }

    #[\Override]
    protected function getMigrationPath(): string
    {
        return $this->resolveLayer()->path.'/database/migrations';
    }
}
