<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Console\Concerns\OffersComposerUpdate;
use Lomkit\Osdd\Console\Concerns\RegistersLayerInComposer;
use Lomkit\Osdd\Console\StubWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'osdd:start')]
class StartCommand extends Command
{
    use OffersComposerUpdate;
    use RegistersLayerInComposer;

    protected $name = 'osdd:start';

    protected $description = 'Prepare a fresh Laravel project for OSDD';

    private const string USERS_LAYER_NAME = 'functional/users';

    private const string USERS_LAYER_NAMESPACE = 'Functional\\Users';

    private const string OSDD_LAYER_NAME = 'technical/osdd';

    public function __construct(protected Filesystem $files, private readonly StubWriter $stubs)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $usersLayerPath = $this->resolveLayerBasePath().'/users';
        $osddLayerPath = $this->resolveTechnicalBasePath().'/osdd';

        $this->createUsersLayer($usersLayerPath);
        $this->createOsddLayer($osddLayerPath);
        $this->deleteDirectory('app');
        $this->deleteDirectory('database');
        $this->deleteDirectory('config');
        $this->cleanComposerAutoload();

        $this->components->info('Congratulations! Your project is ready for OSDD. Create your first layer with <options=bold>php artisan osdd:layer</>.');

        $this->offerComposerUpdate(self::USERS_LAYER_NAME, self::OSDD_LAYER_NAME);

        return self::SUCCESS;
    }

    /**
     * @return array<int, InputOption>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [$this->composerUpdateOption()];
    }

    private function resolveLayerBasePath(): string
    {
        /** @var array<string, string> $paths */
        $paths = config('osdd.layers.paths', []);

        return $paths['functional'] ?? $this->laravel->basePath('functional');
    }

    private function resolveTechnicalBasePath(): string
    {
        /** @var array<string, string> $paths */
        $paths = config('osdd.layers.paths', []);

        return $paths['technical'] ?? $this->laravel->basePath('technical');
    }

    private function createUsersLayer(string $layerPath): void
    {
        $this->stubs->write('layer/composer', $layerPath.'/composer.json', [
            '{{ name }}' => self::USERS_LAYER_NAME,
            '{{ namespace }}' => str_replace('\\', '\\\\', self::USERS_LAYER_NAMESPACE),
        ]);

        $this->stubs->write('start/user-service-provider', $layerPath.'/src/Providers/UsersServiceProvider.php');

        $this->injectProviderInComposerJson(
            $layerPath.'/composer.json',
            self::USERS_LAYER_NAMESPACE.'\\Providers\\UsersServiceProvider',
        );

        $this->registerLayerInComposer(self::USERS_LAYER_NAME, $layerPath);

        $this->stubs->write('start/user-model', $layerPath.'/src/Models/User.php');
        $this->stubs->write('start/user-factory', $layerPath.'/database/factories/UserFactory.php');
        $this->stubs->write('start/users-seeder', $layerPath.'/database/seeders/UsersSeeder.php');
        $this->stubs->write(
            'start/create-users-table',
            $layerPath.'/database/migrations/'.date('Y_m_d_His').'_create_users_table.php',
        );

        $this->components->info('Layer <options=bold>'.self::USERS_LAYER_NAME.'</> created at <options=bold>'.$layerPath.'</>.');
    }

    private function createOsddLayer(string $layerPath): void
    {
        $this->stubs->write('layer/osdd-composer', $layerPath.'/composer.json');
        $this->stubs->write('layer/osdd-config', $layerPath.'/config/osdd.php');
        $this->stubs->write('layer/osdd-service-provider', $layerPath.'/src/Providers/OsddServiceProvider.php');

        $this->registerLayerInComposer(self::OSDD_LAYER_NAME, $layerPath);
        $this->injectOsddProvider();

        $this->components->info('Layer <options=bold>'.self::OSDD_LAYER_NAME.'</> created at <options=bold>'.$layerPath.'</>.');
    }

    private function injectOsddProvider(): void
    {
        $path = $this->laravel->basePath('bootstrap/providers.php');

        if (! $this->files->exists($path)) {
            $this->components->warn('No bootstrap/providers.php found, skipping provider injection.');

            return;
        }

        $this->files->put($path, "<?php\n\nreturn [\n];\n");
    }

    private function cleanComposerAutoload(): void
    {
        $composerPath = $this->laravel->basePath('composer.json');

        if (! $this->files->exists($composerPath)) {
            $this->components->warn('No composer.json found, skipping autoload cleanup.');

            return;
        }

        $composer = ComposerJsonFile::open($this->files, $composerPath);
        $composer->removeAutoloadNamespace('Database\\Factories\\');
        $composer->removeAutoloadNamespace('Database\\Seeders\\');
        $composer->save();

        $this->components->info('Cleaned up legacy autoload entries from composer.json.');
    }

    private function deleteDirectory(string $dir): void
    {
        $path = $this->laravel->basePath($dir);

        if (! $this->files->isDirectory($path)) {
            $this->components->warn("No {$dir}/ directory found, skipping.");

            return;
        }

        if (! $this->files->deleteDirectory($path)) {
            $this->components->error("Failed to delete {$dir}/ directory.");

            return;
        }

        $this->components->info("Deleted {$dir}/ directory.");
    }
}
