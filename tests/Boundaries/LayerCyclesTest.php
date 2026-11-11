<?php

namespace Lomkit\Osdd\Tests\Boundaries;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lomkit\Osdd\Graph\LayerCycles;
use Lomkit\Osdd\Graph\LayerGraph;
use Lomkit\Osdd\Layers\LayerBuckets;
use Lomkit\Osdd\Layers\LayerDiscovery;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Tests\TestCase;

class LayerCyclesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = $this->project()->path('cycles');
    }

    public function test_it_finds_one_shortest_cycle_per_group_of_layers(): void
    {
        $this->layer('a/one', ['a/two']);
        $this->layer('a/two', ['a/three', 'a/one']);
        $this->layer('a/three', ['a/one']);
        $this->layer('a/four', ['a/four']);
        $this->layer('a/five', ['a/one', 'vendor/package']);

        $this->assertSame([
            ['a/four', 'a/four'],
            ['a/one', 'a/two', 'a/one'],
        ], LayerCycles::in(LayerGraph::of($this->discover())));
    }

    public function test_an_acyclic_graph_has_no_cycle(): void
    {
        $this->layer('a/one', ['a/two', 'a/three']);
        $this->layer('a/two', ['a/three']);
        $this->layer('a/three', []);

        $graph = LayerGraph::of($this->discover());

        $this->assertSame([], LayerCycles::in($graph));
        $this->assertSame(['a/three', 'a/two'], $graph->reachableFrom(['a/two']));
    }

    private function discover(): Layers
    {
        return (new LayerDiscovery(new LayerBuckets(['a' => $this->root]), new Filesystem))->discover();
    }

    /**
     * @param  list<string>  $requires
     */
    private function layer(string $name, array $requires): void
    {
        $directory = $this->root.'/'.Str::after($name, '/');
        (new Filesystem)->ensureDirectoryExists($directory);
        file_put_contents($directory.'/composer.json', json_encode([
            'name' => $name,
            'type' => 'layer',
            'require' => array_fill_keys($requires, '*'),
        ]));
    }
}
