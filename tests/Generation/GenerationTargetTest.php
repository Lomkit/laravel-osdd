<?php

namespace Lomkit\Osdd\Tests\Generation;

use Lomkit\Osdd\Generation\GenerationTarget;
use Lomkit\Osdd\Generation\LayerTargetResolver;
use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Tests\TestCase;

class GenerationTargetTest extends TestCase
{
    public function test_classes_go_under_the_layer_source_directory(): void
    {
        $source = $this->target()->source();

        $this->assertSame('Functional\\TestLayer\\', $source->namespace);
        $this->assertSame($this->layerPath('src'), $source->directory);
    }

    public function test_a_conventional_folder_has_the_matching_namespace(): void
    {
        $this->assertSame('Functional\\TestLayer\\Database\\Seeders\\', $this->target()->folder(LayerFolder::Seeders)->namespace);
        $this->assertSame($this->layerPath('database/seeders'), $this->target()->folder(LayerFolder::Seeders)->directory);
        $this->assertSame('Functional\\TestLayer\\Database\\Factories\\', $this->target()->folder(LayerFolder::Factories)->namespace);
        $this->assertSame('Functional\\TestLayer\\Tests\\', $this->target()->folder(LayerFolder::Tests)->namespace);
    }

    public function test_views_go_under_the_layer_views_folder(): void
    {
        $this->assertSame($this->layerPath('resources/views'), $this->target()->viewPath());
        $this->assertSame($this->layerPath('resources/views/components/alert.blade.php'), $this->target()->viewPath('components/alert.blade.php'));
    }

    public function test_a_short_model_name_goes_under_the_layer_models(): void
    {
        $this->assertSame('Functional\\TestLayer\\Models\\Order', $this->target()->modelClass('Order'));
        $this->assertSame('Functional\\TestLayer\\Models\\Admin\\User', $this->target()->modelClass('Admin/User'));
    }

    public function test_an_existing_class_or_a_layer_class_is_kept(): void
    {
        $this->assertSame('Illuminate\\Notifications\\DatabaseNotification', $this->target()->modelClass('\\Illuminate\\Notifications\\DatabaseNotification'));
        $this->assertSame('Functional\\TestLayer\\Domain\\Order', $this->target()->modelClass('Functional\\TestLayer\\Domain\\Order'));
    }

    private function target(): GenerationTarget
    {
        return $this->app->make(LayerTargetResolver::class)->resolve('functional/test-layer', false);
    }

    private function layerPath(string $path): string
    {
        return realpath($this->app->basePath('functional/test-layer')).'/'.$path;
    }
}
