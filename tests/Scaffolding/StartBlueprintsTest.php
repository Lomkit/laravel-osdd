<?php

namespace Lomkit\Osdd\Tests\Scaffolding;

use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Scaffolding\LayerBlueprint;
use Lomkit\Osdd\Scaffolding\StartBlueprints;
use PHPUnit\Framework\TestCase;

class StartBlueprintsTest extends TestCase
{
    public function test_it_drafts_the_users_and_osdd_layers_in_the_buckets_named_after_their_vendor(): void
    {
        $blueprints = StartBlueprints::in(new LayerBuckets(['functional' => '/app/domains', 'technical' => '/app/platform']), '/app');

        $this->assertSame(['functional/users', 'technical/osdd'], array_map(fn (LayerBlueprint $blueprint): string => $blueprint->draft->name->toString(), $blueprints));
        $this->assertSame(['/app/domains/users', '/app/platform/osdd'], array_map(fn (LayerBlueprint $blueprint): string => $blueprint->draft->directory->root, $blueprints));
    }

    public function test_a_missing_bucket_falls_back_to_the_project_directory_of_that_name(): void
    {
        $blueprints = StartBlueprints::in(new LayerBuckets(['functional' => '/app/domains']), '/app');

        $this->assertSame('/app/technical/osdd', $blueprints[1]->draft->directory->root);
    }

    public function test_each_layer_gets_its_provider_and_skeleton_files(): void
    {
        [$users, $osdd] = StartBlueprints::in(new LayerBuckets([]), '/app');

        $this->assertSame([
            'provider',
            'src/Models/User.php',
            'database/factories/UserFactory.php',
            'database/seeders/UsersSeeder.php',
            'database/migrations/0001_01_01_000000_create_users_table.php',
        ], $users->keys());
        $this->assertSame([
            'config/osdd.php',
            'provider',
            'database/migrations/0001_01_01_000001_create_cache_table.php',
            'database/migrations/0001_01_01_000002_create_jobs_table.php',
        ], $osdd->keys());
    }

    public function test_the_osdd_layer_autoloads_its_source_only(): void
    {
        [$users, $osdd] = StartBlueprints::in(new LayerBuckets([]), '/app');

        $this->assertEquals((object) ['Technical\\Osdd\\' => 'src/'], $osdd->draft->manifest->toObject()->autoload->{'psr-4'});
        $this->assertSame('OSDD technical configuration layer', $osdd->draft->manifest->toObject()->description);
        $this->assertCount(3, (array) $users->draft->manifest->toObject()->autoload->{'psr-4'});
    }
}
