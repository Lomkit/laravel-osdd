<?php

namespace Lomkit\Osdd\Tests\Layers;

use Lomkit\Osdd\Layers\AmbiguousLayerNameException;
use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\LayerManifest;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\LayerNotFoundException;
use Lomkit\Osdd\Layers\Layers;
use PHPUnit\Framework\TestCase;

class LayersTest extends TestCase
{
    public function test_layers_are_sorted_by_name(): void
    {
        $this->assertSame(['functional/orders', 'functional/users', 'technical/orders'], $this->layers()->names());
    }

    public function test_find_takes_the_full_name_or_a_unique_package_name(): void
    {
        $layers = $this->layers();

        $this->assertSame('functional/orders', $layers->find('functional/orders')->name->toString());
        $this->assertSame('functional/users', $layers->find('users')->name->toString());
    }

    public function test_find_rejects_a_shared_package_name(): void
    {
        $this->expectException(AmbiguousLayerNameException::class);
        $this->expectExceptionMessage('Layer [orders] is ambiguous: it matches functional/orders, technical/orders.');

        $this->layers()->find('orders');
    }

    public function test_find_suggests_the_closest_name(): void
    {
        $this->expectException(LayerNotFoundException::class);
        $this->expectExceptionMessage('Did you mean [functional/users]?');

        $this->layers()->find('functional/user');
    }

    public function test_find_all_drops_duplicates(): void
    {
        $this->assertSame(['functional/orders', 'functional/users'], $this->layers()->findAll(['users', 'functional/orders', 'functional/users'])->names());
    }

    public function test_the_owning_layer_has_the_longest_matching_prefix(): void
    {
        $layers = new Layers([
            $this->layer('functional/orders', ['Functional\\Orders\\' => 'src/']),
            $this->layer('functional/orders-billing', ['Functional\\Orders\\Billing\\' => 'src/']),
        ]);

        $this->assertSame('functional/orders', $layers->owning('Functional\\Orders\\Models\\Order')?->name->toString());
        $this->assertSame('functional/orders-billing', $layers->owning('\\Functional\\Orders\\Billing\\Invoice')?->name->toString());
        $this->assertNull($layers->owning('App\\Models\\User'));
    }

    public function test_sharing_package_lists_the_other_layers_with_the_same_package(): void
    {
        $layers = $this->layers();

        $this->assertSame(['technical/orders'], $layers->sharingPackage(LayerName::fromString('functional/orders'))->names());
        $this->assertTrue($layers->sharingPackage(LayerName::fromString('functional/users'))->isEmpty());
    }

    public function test_paths_lists_a_folder_of_every_layer(): void
    {
        $this->assertSame([
            '/project/functional/orders/database/migrations',
            '/project/functional/users/database/migrations',
            '/project/technical/orders/database/migrations',
        ], $this->layers()->paths(LayerFolder::Migrations));
    }

    public function test_fail_on_invalid_manifest_throws_the_first_invalid_manifest(): void
    {
        $invalid = new InvalidLayerManifestException('/project/functional/broken/composer.json', 'it has no "name".');
        $layers = new Layers([], [$invalid]);

        $this->assertSame([$invalid], $layers->invalidManifests());
        $valid = $this->layers();
        $this->assertSame($valid, $valid->failOnInvalidManifest());

        $this->expectExceptionObject($invalid);

        $layers->failOnInvalidManifest();
    }

    public function test_layers_are_countable_and_iterable(): void
    {
        $layers = $this->layers();

        $this->assertCount(3, $layers);
        $this->assertSame($layers->names(), array_map(fn (Layer $layer): string => $layer->name->toString(), iterator_to_array($layers)));
    }

    private function layers(): Layers
    {
        return new Layers([
            $this->layer('technical/orders', ['Technical\\Orders\\' => 'src/']),
            $this->layer('functional/users', ['Functional\\Users\\' => 'src/']),
            $this->layer('functional/orders', ['Functional\\Orders\\' => 'src/']),
        ]);
    }

    /**
     * @param  array<string, string>  $psr4
     */
    private function layer(string $name, array $psr4): Layer
    {
        $layer = LayerManifest::fromData(['name' => $name, 'type' => 'layer', 'autoload' => ['psr-4' => $psr4]], '/project/'.$name);
        $this->assertNotNull($layer);

        return $layer;
    }
}
