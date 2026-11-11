<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SeederMakeCommandTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function seeders(): array
    {
        return [
            'seeder' => ['UserSeeder', 'Functional\ProviderLayer\Database\Seeders\UserSeeder'],
            'nested seeder' => ['Admin/AdminSeeder', 'Functional\ProviderLayer\Database\Seeders\Admin\AdminSeeder'],
        ];
    }

    #[DataProvider('seeders')]
    public function test_the_seeder_is_registered_by_the_layer_provider(string $name, string $seeder): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:seeder', ['name' => $name, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $provider->register($this->app);

        $this->assertSame([$seeder], $this->app->make(SeederRegistry::class)->seeders());
    }

    public function test_a_seeder_generated_twice_is_registered_once(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => LayerFixture::PROVIDER_LAYER]);

        $this->assertSame(1, substr_count($provider->source(), 'UserSeeder::class'));
    }

    public function test_without_a_provider_it_writes_the_seeder_and_prints_the_line_to_add(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->expectsOutputToContain('Layer [functional/test-layer] has no service provider.')
            ->expectsOutputToContain('$this->loadSeeders([\Functional\TestLayer\Database\Seeders\UserSeeder::class]);')
            ->assertExitCode(0);

        $this->assertFileExists($this->project()->path('functional/test-layer/database/seeders/UserSeeder.php'));
    }
}
