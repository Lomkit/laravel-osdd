<?php

namespace Lomkit\Osdd\Tests\Generation;

use Lomkit\Osdd\Console\Exceptions\MissingLayerOptionException;
use Lomkit\Osdd\Generation\LayerTargetResolver;
use Lomkit\Osdd\Layers\InvalidLayerManifestException;
use Lomkit\Osdd\Layers\LayerNotFoundException;
use Lomkit\Osdd\Tests\TestCase;

class LayerTargetResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/broken-layer'));

        parent::tearDown();
    }

    public function test_it_targets_the_requested_layer(): void
    {
        $target = $this->resolver()->resolve('test-layer', false);

        $this->assertSame('functional/test-layer', $target->layer->name->toString());
        $this->assertSame('test-layer', $target->resourceNamespace->name());
    }

    public function test_it_requires_a_layer_when_it_cannot_prompt(): void
    {
        $this->expectException(MissingLayerOptionException::class);
        $this->expectExceptionMessage('The --layer option is required when the command is not interactive. Available layers: functional/test-layer.');

        $this->resolver()->resolve(null, false);
    }

    public function test_an_unknown_layer_is_not_found(): void
    {
        $this->expectException(LayerNotFoundException::class);

        $this->resolver()->resolve('functional/billing', false);
    }

    public function test_an_invalid_manifest_stops_the_resolution(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/broken-layer'));
        $this->app['files']->put($this->app->basePath('functional/broken-layer/composer.json'), '{"type": "layer"}');

        $this->expectException(InvalidLayerManifestException::class);

        $this->resolver()->resolve('functional/test-layer', false);
    }

    private function resolver(): LayerTargetResolver
    {
        return $this->app->make(LayerTargetResolver::class);
    }
}
