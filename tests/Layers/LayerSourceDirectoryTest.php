<?php

namespace Lomkit\Osdd\Tests\Layers;

use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Tests\TestCase;

class LayerSourceDirectoryTest extends TestCase
{
    private string $layerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layerPath = $this->app->basePath('functional/app-layer');
        $this->app['files']->ensureDirectoryExists($this->layerPath);
        $this->app['files']->put($this->layerPath.'/composer.json', json_encode([
            'name' => 'functional/app-layer',
            'type' => 'layer',
            'autoload' => ['psr-4' => [
                'Functional\\AppLayer\\Database\\Factories\\' => 'database/factories/',
                'Functional\\AppLayer\\' => 'app/',
            ]],
        ]));
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->layerPath);

        parent::tearDown();
    }

    public function test_the_manifest_takes_the_source_mapping_not_the_first_entry(): void
    {
        $layer = $this->app->make(Layers::class)->find('functional/app-layer');

        $this->assertSame('Functional\\AppLayer\\', $layer->rootNamespace);
        $this->assertSame($layer->directory->root.'/app', $layer->sourceDirectory);
    }

    public function test_generators_write_into_the_source_directory_of_the_layer(): void
    {
        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/app-layer'])->assertExitCode(0);
        $this->artisan('osdd:controller', ['name' => 'Admin/OrderController', '--layer' => 'functional/app-layer'])->assertExitCode(0);

        $this->assertFileExists($this->layerPath.'/app/Models/Order.php');
        $this->assertStringContainsString('namespace Functional\\AppLayer\\Models;', (string) file_get_contents($this->layerPath.'/app/Models/Order.php'));
        $this->assertFileExists($this->layerPath.'/app/Http/Controllers/Admin/OrderController.php');
        $this->assertDirectoryDoesNotExist($this->layerPath.'/src');
    }
}
