<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InvalidLayerManifestTest extends TestCase
{
    private string $brokenLayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brokenLayer = $this->app->basePath('functional/broken');
        $this->app['files']->ensureDirectoryExists($this->brokenLayer);
        $this->app['files']->put($this->brokenLayer.'/composer.json', '{"name": "functional/broken", "type": "layer",');
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->brokenLayer);

        parent::tearDown();
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function commandsReadingTheLayers(): array
    {
        return [
            'generator' => ['osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer']],
            'seed with --layer' => ['osdd:seed', ['--layer' => ['functional/test-layer']]],
            'check' => ['osdd:check', []],
            'graph' => ['osdd:graph', []],
            'phpunit' => ['osdd:phpunit', ['--prune' => true]],
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    #[DataProvider('commandsReadingTheLayers')]
    public function test_a_command_reading_the_layers_names_the_invalid_manifest_and_fails(string $command, array $parameters): void
    {
        $this->assertSame(1, Artisan::call($command, $parameters));
        $this->assertStringContainsString('Invalid layer manifest [functional/broken/composer.json]: it is not valid JSON', Artisan::output());

        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/src/Models/Order.php'));
    }
}
