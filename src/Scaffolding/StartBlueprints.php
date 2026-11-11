<?php

namespace Lomkit\Osdd\Scaffolding;

use Lomkit\Osdd\Composer\LayerComposerManifest;
use Lomkit\Osdd\Console\Providers\Statements\LoadSeeders;
use Lomkit\Osdd\Console\Providers\Statements\OverrideConfig;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Scaffolding\Scaffolds\ProviderScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\StubScaffold;
use Lomkit\Osdd\Stubs\Stub;

final class StartBlueprints
{
    private const string USERS_LAYER = 'functional/users';

    private const string OSDD_LAYER = 'technical/osdd';

    private const string OSDD_LAYER_DESCRIPTION = 'OSDD technical configuration layer';

    private const string USERS_SEEDER = 'UsersSeeder';

    private const string OSDD_CONFIG_FILE = 'osdd.php';

    /**
     * The layers osdd:start creates, each in the bucket named after its vendor, or in the project directory of that name.
     *
     * @return list<LayerBlueprint>
     */
    public static function in(LayerBuckets $buckets, string $basePath): array
    {
        return [self::users($buckets, $basePath), self::osdd($buckets, $basePath)];
    }

    private static function users(LayerBuckets $buckets, string $basePath): LayerBlueprint
    {
        $name = LayerName::fromString(self::USERS_LAYER);
        $seeder = $name->namespace().'\\'.LayerFolder::Seeders->namespace().'\\'.self::USERS_SEEDER;

        return new LayerBlueprint(LayerDraft::inBucket(self::bucketPath($name, $buckets, $basePath), LayerComposerManifest::for($name)), [
            new ProviderScaffold(Stub::UsersProvider, [new LoadSeeders($seeder)]),
            new StubScaffold(Stub::UserModel, LayerComposerManifest::SOURCE_DIRECTORY.'/Models/User.php'),
            new StubScaffold(Stub::UserFactory, LayerFolder::Factories->value.'/UserFactory.php'),
            new StubScaffold(Stub::UsersSeeder, LayerFolder::Seeders->value.'/'.self::USERS_SEEDER.'.php'),
            new StubScaffold(Stub::CreateUsersTable, LayerFolder::Migrations->value.'/0001_01_01_000000_create_users_table.php'),
        ]);
    }

    private static function osdd(LayerBuckets $buckets, string $basePath): LayerBlueprint
    {
        $name = LayerName::fromString(self::OSDD_LAYER);
        $manifest = LayerComposerManifest::for($name, description: self::OSDD_LAYER_DESCRIPTION, autoloadedFolders: []);

        return new LayerBlueprint(LayerDraft::inBucket(self::bucketPath($name, $buckets, $basePath), $manifest), [
            new StubScaffold(Stub::OsddConfig, LayerFolder::Config->value.'/'.self::OSDD_CONFIG_FILE),
            new ProviderScaffold(Stub::OsddProvider, [new OverrideConfig(self::OSDD_CONFIG_FILE)]),
            new StubScaffold(Stub::CreateCacheTable, LayerFolder::Migrations->value.'/0001_01_01_000001_create_cache_table.php'),
            new StubScaffold(Stub::CreateJobsTable, LayerFolder::Migrations->value.'/0001_01_01_000002_create_jobs_table.php'),
        ]);
    }

    private static function bucketPath(LayerName $name, LayerBuckets $buckets, string $basePath): string
    {
        return $buckets->has($name->vendor) ? $buckets->path($name->vendor) : $basePath.'/'.$name->vendor;
    }
}
