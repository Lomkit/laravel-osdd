<?php

namespace Lomkit\Osdd\Tests\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\LayerComposerManifest;
use Lomkit\Osdd\Layers\LayerManifest;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Scaffolding\LayerComposerJson;
use PHPUnit\Framework\TestCase;

class LayerComposerManifestTest extends TestCase
{
    private const string LAYER_COMPOSER_JSON = <<<'JSON'
        {
            "name": "functional/sales-orders",
            "description": "",
            "type": "layer",
            "version": "1.0.0",
            "require": {
                "lomkit/laravel-osdd": "*"
            },
            "autoload": {
                "psr-4": {
                    "Functional\\SalesOrders\\": "src/",
                    "Functional\\SalesOrders\\Database\\Seeders\\": "database/seeders/",
                    "Functional\\SalesOrders\\Database\\Factories\\": "database/factories/"
                }
            },
            "minimum-stability": "stable",
            "prefer-stable": true
        }

        JSON;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/osdd-layer-manifest-'.uniqid();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_a_new_layer_gets_the_composer_json_of_2x_byte_for_byte(): void
    {
        $this->write(LayerComposerManifest::for(LayerName::fromString('functional/sales-orders')));

        $this->assertSame(self::LAYER_COMPOSER_JSON, file_get_contents($this->path()));
    }

    public function test_the_written_manifest_is_a_valid_layer(): void
    {
        $this->write(LayerComposerManifest::for(LayerName::fromString('functional/sales-orders'), requiredLayers: ['technical/billing']));

        $layer = LayerManifest::parse((string) file_get_contents($this->path()), $this->directory);

        $this->assertNotNull($layer);
        $this->assertSame('functional/sales-orders', $layer->name->toString());
        $this->assertSame('Functional\\SalesOrders\\', $layer->rootNamespace);
        $this->assertSame(['lomkit/laravel-osdd', 'technical/billing'], $layer->requires);
    }

    public function test_a_layer_without_database_namespaces_and_with_a_description(): void
    {
        $manifest = LayerComposerManifest::for(LayerName::fromString('technical/osdd'), description: 'OSDD technical configuration layer', autoloadedFolders: []);

        $object = $manifest->toObject();

        $this->assertSame('OSDD technical configuration layer', $object->description);
        $this->assertEquals((object) ['Technical\\Osdd\\' => 'src/'], $object->autoload->{'psr-4'});
        $this->assertEquals((object) ['lomkit/laravel-osdd' => '*'], $object->require);
    }

    public function test_an_existing_composer_json_keeps_its_values_and_gains_the_missing_keys(): void
    {
        mkdir($this->directory, 0755, true);
        file_put_contents($this->path(), "{\n  \"name\": \"functional/sales-orders\",\n  \"description\": \"Sales\",\n  \"require\": {\"functional/users\": \"^1.0\"}\n}\n");

        $this->write(LayerComposerManifest::for(LayerName::fromString('functional/sales-orders'), requiredLayers: ['functional/users', 'technical/billing']));

        $composer = json_decode((string) file_get_contents($this->path()), true);
        $this->assertSame('Sales', $composer['description']);
        $this->assertSame(['functional/users' => '^1.0', 'lomkit/laravel-osdd' => '*', 'technical/billing' => '*'], $composer['require']);
        $this->assertSame('layer', $composer['type']);
        $this->assertStringContainsString("\n  \"type\": \"layer\"", (string) file_get_contents($this->path()));
    }

    private function write(LayerComposerManifest $manifest): void
    {
        (new LayerComposerJson(new Filesystem))->write($manifest, $this->path());
    }

    private function path(): string
    {
        return $this->directory.'/composer.json';
    }
}
