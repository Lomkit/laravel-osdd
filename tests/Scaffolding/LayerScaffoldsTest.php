<?php

namespace Lomkit\Osdd\Tests\Scaffolding;

use Lomkit\Osdd\Console\Exceptions\UnknownLayerGeneratorException;
use Lomkit\Osdd\Scaffolding\DuplicateScaffoldKeyException;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\LayerScaffolds;
use Lomkit\Osdd\Scaffolding\Scaffolds\PolicyScaffold;
use Lomkit\Osdd\Tests\Scaffolding\Fixtures\KeyedScaffold;
use Lomkit\Osdd\Tests\TestCase;

class LayerScaffoldsTest extends TestCase
{
    public function test_the_standard_scaffolds_keep_the_osdd_layer_generator_names_and_defaults(): void
    {
        $scaffolds = LayerScaffolds::standard();

        $this->assertSame(['migration', 'model', 'factory', 'seeder', 'provider', 'test', 'controller', 'policy', 'routes'], $scaffolds->keys());
        $this->assertSame(['migration', 'model', 'factory', 'provider', 'test'], $scaffolds->defaultKeys());
    }

    public function test_the_container_holds_the_standard_scaffolds(): void
    {
        $this->assertSame($this->app->make(LayerScaffolds::class), $this->app->make(LayerScaffolds::class));
        $this->assertSame(LayerScaffolds::standard()->keys(), $this->app->make(LayerScaffolds::class)->keys());
    }

    public function test_it_parses_repeated_and_comma_separated_keys_once_each_in_the_given_order(): void
    {
        $parsed = LayerScaffolds::standard()->parse(['policy, model', 'routes,,policy', ' model ']);

        $this->assertSame(['policy', 'model', 'routes'], array_map(fn (LayerScaffold $scaffold): string => $scaffold->key(), $parsed));
        $this->assertInstanceOf(PolicyScaffold::class, $parsed[0]);
    }

    public function test_an_unknown_key_lists_the_valid_ones(): void
    {
        $this->expectException(UnknownLayerGeneratorException::class);
        $this->expectExceptionMessage('Unknown generator [modle]. Valid generators: model, routes.');

        (new LayerScaffolds([new KeyedScaffold('model'), new KeyedScaffold('routes')], []))->parse(['modle']);
    }

    public function test_a_default_must_be_one_of_the_scaffolds(): void
    {
        $this->expectException(UnknownLayerGeneratorException::class);

        new LayerScaffolds([new KeyedScaffold('model')], ['provider']);
    }

    public function test_two_scaffolds_cannot_share_a_key(): void
    {
        $this->expectException(DuplicateScaffoldKeyException::class);

        new LayerScaffolds([new KeyedScaffold('model'), new KeyedScaffold('model')], []);
    }
}
