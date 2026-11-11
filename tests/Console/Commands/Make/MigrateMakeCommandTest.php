<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;

class MigrateMakeCommandTest extends TestCase
{
    public function test_the_layer_provider_loads_the_migration_and_it_migrates(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:migration', ['name' => 'create_parcels_table', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $provider->register($this->app);
        $this->artisan('migrate')->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('parcels'));
        $this->assertCount(1, glob($this->project()->path(LayerFixture::PROVIDER_LAYER.'/database/migrations/*_create_parcels_table.php')) ?: []);
        $this->assertSame([], glob($this->project()->path('database/migrations/*.php')) ?: []);
    }

    public function test_the_create_option_names_the_table(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:migration', ['name' => 'add_carriers', '--create' => 'carriers', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $provider->register($this->app);
        $this->artisan('migrate')->assertExitCode(0);

        $this->assertTrue(Schema::hasTable('carriers'));
    }

    public function test_a_second_migration_does_not_register_the_folder_again(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:migration', ['name' => 'create_parcels_table', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:migration', ['name' => 'create_carriers_table', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame(1, substr_count($provider->source(), 'loadMigrationsFrom('));
    }

    public function test_without_a_provider_it_writes_the_migration_and_prints_the_line_to_add(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_parcels_table', '--layer' => 'functional/test-layer'])
            ->expectsOutputToContain('Layer [functional/test-layer] has no service provider.')
            ->expectsOutputToContain("\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');")
            ->assertExitCode(0);

        $this->assertCount(1, glob($this->project()->path('functional/test-layer/database/migrations/*_create_parcels_table.php')) ?: []);
    }

    public function test_it_asks_for_the_layer_when_none_is_given(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_parcels_table'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertCount(1, glob($this->project()->path('functional/test-layer/database/migrations/*_create_parcels_table.php')) ?: []);
    }
}
