<?php

namespace Lomkit\Osdd\Tests\Layers;

use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\LayerManifest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LayerManifestTest extends TestCase
{
    private const string DIRECTORY = '/project/functional/orders';

    public function test_a_layer_manifest_becomes_a_layer(): void
    {
        $layer = LayerManifest::fromData([
            'name' => 'functional/orders',
            'type' => 'layer',
            'require' => ['laravel/framework' => '^13.0', 'technical/billing' => '*'],
            'require-dev' => ['functional/users' => '*'],
            'autoload' => ['psr-4' => [
                'Functional\\Orders\\Database\\Factories\\' => 'database/factories/',
                'Functional\\Orders\\' => 'src/',
            ]],
            'autoload-dev' => ['psr-4' => ['Functional\\Orders\\Tests\\' => 'tests/']],
            'extra' => ['laravel' => ['providers' => ['Functional\\Orders\\Providers\\OrdersServiceProvider']]],
        ], self::DIRECTORY);

        $this->assertNotNull($layer);
        $this->assertSame('functional/orders', $layer->name->toString());
        $this->assertSame(self::DIRECTORY, $layer->directory->root);
        $this->assertSame('Functional\\Orders\\', $layer->rootNamespace);
        $this->assertSame(self::DIRECTORY.'/src', $layer->sourceDirectory);
        $this->assertSame(['laravel/framework', 'technical/billing'], $layer->requires);
        $this->assertSame(['functional/users'], $layer->devRequires);
        $this->assertSame(['Functional\\Orders\\Tests\\'], $layer->devNamespaces());
        $this->assertSame(['Functional\\Orders\\Providers\\OrdersServiceProvider'], $layer->providers);
        $this->assertSame(self::DIRECTORY.'/src/Providers/OrdersServiceProvider.php', $layer->classFile('Functional\\Orders\\Providers\\OrdersServiceProvider'));
        $this->assertSame(self::DIRECTORY.'/database/factories/OrderFactory.php', $layer->classFile('\\Functional\\Orders\\Database\\Factories\\OrderFactory'));
        $this->assertNull($layer->classFile('Functional\\Users\\Models\\User'));
    }

    public function test_a_layer_without_psr4_defaults_to_src_and_an_empty_namespace(): void
    {
        $layer = LayerManifest::fromData(['name' => 'functional/orders', 'type' => 'layer'], self::DIRECTORY);

        $this->assertNotNull($layer);
        $this->assertSame('', $layer->rootNamespace);
        $this->assertSame(self::DIRECTORY.'/src', $layer->sourceDirectory);
        $this->assertSame([], $layer->providers);
    }

    public function test_a_composer_json_of_another_type_is_not_a_layer(): void
    {
        $this->assertNull(LayerManifest::parse('{"name": "acme/tool", "type": "library"}', self::DIRECTORY));
        $this->assertNull(LayerManifest::parse('{"name": "acme/tool"}', self::DIRECTORY));
    }

    public function test_the_manifest_round_trips_through_its_cached_form(): void
    {
        $layer = LayerManifest::fromData([
            'name' => 'functional/orders',
            'type' => 'layer',
            'require' => ['technical/billing' => '^1.0'],
            'autoload' => ['psr-4' => ['Functional\\Orders\\' => 'app/']],
            'extra' => ['laravel' => ['providers' => ['Functional\\Orders\\OrdersServiceProvider']]],
        ], self::DIRECTORY);

        $this->assertNotNull($layer);
        $this->assertEquals($layer, LayerManifest::fromData($layer->toManifest(), self::DIRECTORY));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidManifests(): array
    {
        return [
            'broken json' => ['{"name": "functional/orders", "type": "layer",', 'it is not valid JSON'],
            'not an object' => ['"layer"', 'it is not a JSON object.'],
            'no name' => ['{"type": "layer"}', 'it has no "name".'],
            'invalid name' => ['{"name": "Functional/Orders", "type": "layer"}', 'Invalid layer name [Functional/Orders].'],
            'psr-4 not a map' => ['{"name": "functional/orders", "type": "layer", "autoload": {"psr-4": "src/"}}', '"autoload.psr-4" must map namespaces to directories.'],
            'require not a map' => ['{"name": "functional/orders", "type": "layer", "require": "technical/billing"}', '"require" must map package names to constraints.'],
            'providers not a list' => ['{"name": "functional/orders", "type": "layer", "extra": {"laravel": {"providers": "App\\\\Provider"}}}', '"extra.laravel.providers" must be a list of class names.'],
        ];
    }

    #[DataProvider('invalidManifests')]
    public function test_an_invalid_layer_manifest_names_its_file(string $json, string $reason): void
    {
        try {
            LayerManifest::parse($json, self::DIRECTORY);
            $this->fail('The manifest was accepted.');
        } catch (InvalidLayerManifestException $exception) {
            $this->assertSame(self::DIRECTORY.'/composer.json', $exception->manifestFile);
            $this->assertStringStartsWith($reason, $exception->reason);
            $this->assertStringStartsWith('Invalid layer manifest ['.self::DIRECTORY.'/composer.json]: '.$reason, $exception->getMessage());
        }
    }

    public function test_a_psr4_entry_with_several_directories_uses_the_first_one(): void
    {
        $layer = LayerManifest::parse('{"name": "functional/orders", "type": "layer", "autoload": {"psr-4": {"Functional\\\\Orders\\\\": ["app/", "lib/"]}}}', self::DIRECTORY);

        $this->assertSame(self::DIRECTORY.'/app', $layer?->sourceDirectory);
    }
}
