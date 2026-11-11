<?php

namespace Lomkit\Osdd\Tests\Layers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerDiscovery;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Tests\TestCase;

class LayerDiscoveryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/osdd-discovery-'.Str::random(8);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_it_finds_layers_in_every_bucket_and_in_plain_subdirectories(): void
    {
        $this->manifest('functional/orders', ['name' => 'functional/orders', 'type' => 'layer']);
        $this->manifest('functional/billing/invoices', ['name' => 'functional/invoices', 'type' => 'layer']);
        $this->manifest('technical/mail', ['name' => 'technical/mail', 'type' => 'layer']);

        $layers = $this->discover();

        $this->assertSame(['functional/invoices', 'functional/orders', 'technical/mail'], $layers->names());
        $this->assertSame($this->realRoot().'/functional/billing/invoices', $layers->find('functional/invoices')->directory->root);
    }

    public function test_it_does_not_look_inside_a_layer_or_another_package(): void
    {
        $this->manifest('functional/orders', ['name' => 'functional/orders', 'type' => 'layer']);
        $this->manifest('functional/orders/modules/nested', ['name' => 'functional/nested', 'type' => 'layer']);
        $this->manifest('functional/tool', ['name' => 'acme/tool', 'type' => 'library']);
        $this->manifest('functional/tool/packages/inner', ['name' => 'functional/inner', 'type' => 'layer']);

        $this->assertSame(['functional/orders'], $this->discover()->names());
    }

    public function test_invalid_manifests_are_collected_instead_of_thrown(): void
    {
        $this->manifest('functional/orders', ['name' => 'functional/orders', 'type' => 'layer']);
        $this->manifest('functional/broken', []);
        (new Filesystem)->put($this->root.'/functional/broken/composer.json', '{');
        $this->manifest('functional/nameless', ['type' => 'layer']);

        $layers = $this->discover();

        $this->assertSame(['functional/orders'], $layers->names());
        $this->assertSame(
            [$this->realRoot().'/functional/broken/composer.json', $this->realRoot().'/functional/nameless/composer.json'],
            array_map(fn ($exception): string => $exception->manifestFile, $layers->invalidManifests()),
        );
    }

    public function test_each_composer_json_is_read_once(): void
    {
        $this->manifest('functional/orders', ['name' => 'functional/orders', 'type' => 'layer']);
        $this->manifest('functional/tool', ['name' => 'acme/tool', 'type' => 'library']);
        $files = new class extends Filesystem
        {
            /** @var array<string, int> */
            public array $reads = [];

            public function get($path, $lock = false)
            {
                $this->reads[$path] = ($this->reads[$path] ?? 0) + 1;

                return parent::get($path, $lock);
            }
        };

        (new LayerDiscovery(new LayerBuckets(['functional' => $this->root.'/functional']), $files))->discover();

        $this->assertSame([
            $this->realRoot().'/functional/orders/composer.json' => 1,
            $this->realRoot().'/functional/tool/composer.json' => 1,
        ], $files->reads);
    }

    public function test_a_missing_bucket_has_no_layer(): void
    {
        $this->assertTrue((new LayerDiscovery(new LayerBuckets(['functional' => $this->root.'/missing']), new Filesystem))->discover()->isEmpty());
    }

    public function test_the_container_discovers_the_configured_buckets_once(): void
    {
        $this->app['config']->set('osdd.layers.paths', ['functional' => $this->root.'/functional']);
        $this->manifest('functional/orders', ['name' => 'functional/orders', 'type' => 'layer']);

        $layers = $this->app->make(Layers::class);

        $this->assertSame(['functional/orders'], $layers->names());
        $this->assertSame($layers, $this->app->make(Layers::class));
    }

    private function discover(): Layers
    {
        return (new LayerDiscovery(new LayerBuckets([
            'functional' => $this->root.'/functional',
            'technical' => $this->root.'/technical',
        ]), new Filesystem))->discover();
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function manifest(string $directory, array $manifest): void
    {
        $files = new Filesystem;
        $files->ensureDirectoryExists($this->root.'/'.$directory);
        $files->put($this->root.'/'.$directory.'/composer.json', (string) json_encode($manifest));
    }

    private function realRoot(): string
    {
        return (string) realpath($this->root);
    }
}
