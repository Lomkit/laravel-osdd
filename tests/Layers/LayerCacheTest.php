<?php

namespace Lomkit\Osdd\Tests\Layers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerCache;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Tests\Concerns\CreatesLayers;
use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class LayerCacheTest extends TestCase
{
    use CreatesLayers;

    private const string OPTIMIZE_TASKS_OF_THE_FRAMEWORK = 'config,events,routes,views';

    protected function tearDown(): void
    {
        $this->app['files']->delete($this->cacheFile());
        $this->deleteCreatedLayers();

        parent::tearDown();
    }

    public function test_osdd_cache_writes_the_discovered_layers_to_bootstrap_cache(): void
    {
        $this->artisan('osdd:cache')
            ->expectsOutputToContain('1 layer cached successfully.')
            ->assertExitCode(0);

        $this->assertFileExists($this->cacheFile());
        $this->assertSame(['functional/test-layer'], $this->layersOfANewProcess()->names());
    }

    public function test_the_next_process_reads_the_cache_instead_of_scanning(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);
        $this->createLayer($this->app->basePath('functional/added-later'), 'functional/added-later');

        $this->assertSame(['functional/test-layer'], $this->layersOfANewProcess()->names());
    }

    public function test_the_cached_layers_keep_everything_the_manifest_declared(): void
    {
        $this->createLayer($this->app->basePath('functional/orders'), 'functional/orders', [
            'require' => ['functional/test-layer' => '*'],
            'require-dev' => ['functional/users' => '*'],
            'autoload' => ['psr-4' => ['Functional\\Orders\\' => 'app/']],
            'autoload-dev' => ['psr-4' => ['Functional\\Orders\\Tests\\' => 'tests/']],
            'extra' => ['laravel' => ['providers' => ['Functional\\Orders\\OrdersServiceProvider']]],
        ]);
        $discovered = $this->app->make(Layers::class)->find('functional/orders');

        $this->artisan('osdd:cache')->assertExitCode(0);
        $this->createLayer($this->app->basePath('functional/added-later'), 'functional/added-later');

        $this->assertEquals($discovered, $this->layersOfANewProcess()->find('functional/orders'));
    }

    public function test_osdd_clear_removes_the_cache(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);
        $this->createLayer($this->app->basePath('functional/added-later'), 'functional/added-later');

        $this->artisan('osdd:clear')
            ->expectsOutputToContain('Layer cache cleared successfully.')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->cacheFile());
        $this->assertSame(['functional/added-later', 'functional/test-layer'], $this->layersOfANewProcess()->names());
    }

    public function test_a_cache_built_for_other_layer_paths_is_ignored(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);
        $this->createLayer($this->app->basePath('technical/mail'), 'technical/mail');
        $this->createdLayerPaths[] = $this->app->basePath('technical');

        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));

        $this->assertSame(['functional/test-layer', 'technical/mail'], $this->layersOfANewProcess()->names());
    }

    public function test_an_unreadable_cache_is_ignored(): void
    {
        $this->app['files']->put($this->cacheFile(), "<?php return 'not a cache';\n");

        $this->assertSame(['functional/test-layer'], $this->layersOfANewProcess()->names());
    }

    public function test_osdd_cache_refuses_invalid_manifests_and_leaves_no_cache(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);
        $this->createLayer($this->app->basePath('functional/broken'), 'functional/broken');
        $this->app['files']->put($this->app->basePath('functional/broken/composer.json'), '{');

        $this->assertSame(1, Artisan::call('osdd:cache'));
        $this->assertStringContainsString('Invalid layer manifest', Artisan::output());
        $this->assertFileDoesNotExist($this->cacheFile());
    }

    public function test_osdd_layer_refreshes_an_existing_cache(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);

        $this->createdLayerPaths[] = $this->app->basePath('functional/invoices');
        $this->artisan('osdd:layer', ['name' => 'functional/invoices', '--generators' => ['model'], '--no-composer-update' => true])->assertExitCode(0);

        $this->assertSame(['functional/invoices', 'functional/test-layer'], $this->layersOfANewProcess()->names());
    }

    public function test_package_discovery_refreshes_an_existing_cache(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);
        $this->createLayer($this->app->basePath('functional/added-later'), 'functional/added-later');

        $this->app['events']->dispatch(new CommandFinished('package:discover', new ArrayInput([]), new NullOutput, 0));

        $this->assertSame(['functional/added-later', 'functional/test-layer'], $this->layersOfANewProcess()->names());
    }

    public function test_package_discovery_does_not_create_a_cache(): void
    {
        $this->app['events']->dispatch(new CommandFinished('package:discover', new ArrayInput([]), new NullOutput, 0));

        $this->assertFileDoesNotExist($this->cacheFile());
    }

    public function test_optimize_caches_the_layers_and_optimize_clear_removes_them(): void
    {
        Artisan::call('optimize', ['--except' => self::OPTIMIZE_TASKS_OF_THE_FRAMEWORK]);

        $this->assertStringContainsString('osdd', Artisan::output());
        $this->assertFileExists($this->cacheFile());

        Artisan::call('optimize:clear', ['--except' => self::OPTIMIZE_TASKS_OF_THE_FRAMEWORK.',cache,compiled']);

        $this->assertStringContainsString('osdd', Artisan::output());
        $this->assertFileDoesNotExist($this->cacheFile());
    }

    private function layersOfANewProcess(): Layers
    {
        $this->app->forgetInstance(LayerBuckets::class);
        $this->app->forgetInstance(LayerCache::class);
        $this->app->forgetInstance(Layers::class);

        return $this->app->make(Layers::class);
    }

    private function cacheFile(): string
    {
        return $this->app->bootstrapPath('cache/osdd.php');
    }
}
