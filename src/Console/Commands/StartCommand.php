<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Concerns\OffersComposerUpdate;
use Lomkit\Osdd\Console\Concerns\RegistersLayerInComposer;
use Lomkit\Osdd\Console\Start\SkeletonCleaner;
use Lomkit\Osdd\Console\StubWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

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
        $usersLayerPath = $this->bucketPath('functional').'/users';
        $osddLayerPath = $this->bucketPath('technical').'/osdd';

        foreach ([$usersLayerPath, $osddLayerPath] as $layerPath) {
            if ($this->files->exists($layerPath)) {
                $this->components->error("[{$layerPath}] already exists: osdd:start has already run on this project.");

                return self::FAILURE;
            }
        }

        if (! $this->confirmed()) {
            return self::FAILURE;
        }

        $this->createUsersLayer($usersLayerPath);
        $this->createOsddLayer($osddLayerPath);

        (new SkeletonCleaner($this->files, $this->components, $this->laravel->basePath()))->clean();

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
        return [
            new InputOption('force', null, InputOption::VALUE_NONE, 'Delete app/, config/ and database/ without asking'),
            $this->composerUpdateOption(),
        ];
    }

    private function confirmed(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->canPrompt()) {
            $this->components->error('osdd:start deletes app/, config/ and database/. Pass --force to run it without interaction.');

            return false;
        }

        if (confirm('osdd:start deletes app/, config/ and database/ (database/database.sqlite is kept). Continue?', default: false)) {
            return true;
        }

        $this->components->warn('Nothing was changed.');

        return false;
    }

    private function bucketPath(string $bucket): string
    {
        /** @var array<string, string> $paths */
        $paths = config('osdd.layers.paths', []);

        return $paths[$bucket] ?? $this->laravel->basePath($bucket);
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
        $this->stubs->write('start/create-users-table', $layerPath.'/database/migrations/0001_01_01_000000_create_users_table.php');

        $this->components->info('Layer <options=bold>'.self::USERS_LAYER_NAME.'</> created at <options=bold>'.$layerPath.'</>.');
    }

    private function createOsddLayer(string $layerPath): void
    {
        $this->stubs->write('layer/osdd-composer', $layerPath.'/composer.json');
        $this->stubs->write('layer/osdd-config', $layerPath.'/config/osdd.php');
        $this->stubs->write('layer/osdd-service-provider', $layerPath.'/src/Providers/OsddServiceProvider.php');
        $this->stubs->write('start/create-cache-table', $layerPath.'/database/migrations/0001_01_01_000001_create_cache_table.php');
        $this->stubs->write('start/create-jobs-table', $layerPath.'/database/migrations/0001_01_01_000002_create_jobs_table.php');

        $this->registerLayerInComposer(self::OSDD_LAYER_NAME, $layerPath);

        $this->components->info('Layer <options=bold>'.self::OSDD_LAYER_NAME.'</> created at <options=bold>'.$layerPath.'</>.');
    }
}
