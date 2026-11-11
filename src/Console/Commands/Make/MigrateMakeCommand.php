<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Facades\Config;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:migration')]
class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    private const string CENTRAL_FOLDER = 'database/migrations';

    private const string DEFAULT_TENANT_FOLDER = 'database/migrations/tenant';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'osdd:migration {name : The name of the migration}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--fullpath : Output the full path of the migration (Deprecated)}
        {--tenant : Write the migration for the tenant databases and register it with loadTenantMigrationsFrom()}
        {--layer= : The layer to generate the file in}';

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        $folder = $this->migrationFolder();
        $loader = $this->option('tenant') ? 'loadTenantMigrationsFrom' : 'loadMigrationsFrom';

        $this->registerInLayerProvider(
            ProviderMethod::Boot,
            fn (LayerProvider $provider): string => "\$this->{$loader}({$provider->pathExpression($folder)});",
            $folder,
        );
    }

    #[\Override]
    protected function getMigrationPath(): string
    {
        return $this->resolveLayer()->path.'/'.$this->migrationFolder();
    }

    private function migrationFolder(): string
    {
        if (! $this->option('tenant')) {
            return self::CENTRAL_FOLDER;
        }

        return trim(Config::string('osdd.tenancy.migrations', self::DEFAULT_TENANT_FOLDER), '/');
    }
}
