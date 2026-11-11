<?php

namespace Lomkit\Osdd\Tests\Composer;

use Lomkit\Osdd\Composer\PathRepository;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Tests\Fixtures\UsesTemporaryProject;
use PHPUnit\Framework\TestCase;

class PathRepositoryTest extends TestCase
{
    use UsesTemporaryProject;

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = $this->project()->path('path-repository');
        mkdir($this->basePath.'/functional/orders', 0755, true);
        mkdir($this->basePath.'/technical/billing', 0755, true);
    }

    public function test_urls_naming_the_same_directory_are_the_same_repository(): void
    {
        $repository = PathRepository::fromUrl($this->basePath, './functional/*');

        $this->assertTrue($repository->isSameAs(PathRepository::fromUrl($this->basePath, 'functional/*')));
        $this->assertTrue($repository->isSameAs(PathRepository::fromUrl($this->basePath, $this->basePath.'/functional/*')));
        $this->assertTrue($repository->isSameAs(PathRepository::fromUrl($this->basePath, './technical/../functional/*')));
        $this->assertFalse($repository->isSameAs(PathRepository::fromUrl($this->basePath, './technical/*')));
        $this->assertFalse($repository->isSameAs(PathRepository::fromUrl($this->basePath, './functional/orders')));
    }

    public function test_a_wildcard_repository_covers_the_directories_it_matches(): void
    {
        $functional = PathRepository::fromUrl($this->basePath, './functional/*');

        $this->assertTrue($functional->covers($this->basePath.'/functional/orders'));
        $this->assertTrue($functional->covers($this->basePath.'/technical/../functional/orders'));
        $this->assertFalse($functional->covers($this->basePath.'/technical/billing'));
        $this->assertFalse($functional->covers($this->basePath.'/functional/missing'));
        $this->assertTrue(PathRepository::fromUrl($this->basePath, 'technical/billing')->covers($this->basePath.'/technical/billing'));
    }

    public function test_the_repository_of_a_layer_covers_its_bucket(): void
    {
        $repository = PathRepository::forLayerDirectory($this->basePath, new LayerDirectory($this->basePath.'/functional/orders'));

        $this->assertSame('./functional/*', $repository->url);
        $this->assertEquals((object) ['type' => 'path', 'url' => './functional/*'], $repository->toObject());
        $this->assertTrue($repository->covers($this->basePath.'/functional/orders'));
    }
}
